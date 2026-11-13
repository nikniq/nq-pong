const WIDTH = 960;
const HEIGHT = 600;
const WINNING_SCORE = 7;

const PADDLE = {
    width: 16,
    height: 112,
    margin: 44,
    speed: 640,
};

const BALL = {
    radius: 10,
    maxSpeed: 1180,
    speedUp: 1.055,
    step: 8,
};

const DIFFICULTIES = {
    chill: { label: 'Chill', ballSpeed: 380, paddleSpeed: 320, error: 88 },
    classic: { label: 'Classic', ballSpeed: 440, paddleSpeed: 470, error: 44 },
    insane: { label: 'Insane', ballSpeed: 500, paddleSpeed: 690, error: 12 },
};

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
const random = (min, max) => min + Math.random() * (max - min);

const formatDuration = (seconds) => {
    const total = Math.max(0, Math.round(seconds));
    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
};

class PongGame {
    constructor(root) {
        this.root = root;
        this.canvas = root.querySelector('[data-pong-canvas]');
        this.ctx = this.canvas.getContext('2d');

        this.overlay = root.querySelector('[data-overlay]');
        this.overlayKicker = root.querySelector('[data-overlay-kicker]');
        this.overlayTitle = root.querySelector('[data-overlay-title]');
        this.overlayCopy = root.querySelector('[data-overlay-copy]');
        this.overlayActions = root.querySelector('[data-overlay-actions]');
        this.overlayExtra = root.querySelector('[data-overlay-extra]');
        this.scoreTemplate = document.querySelector('[data-score-template]');

        this.leftScoreEl = root.querySelector('[data-score-left]');
        this.rightScoreEl = root.querySelector('[data-score-right]');
        this.statusEl = root.querySelector('[data-match-status]');
        this.liveStatusEl = root.querySelector('[data-live-status]');
        this.leftLabelEl = root.querySelector('[data-left-label]');
        this.rightLabelEl = root.querySelector('[data-right-label]');

        this.modeButtons = [...root.querySelectorAll('[data-mode]')];
        this.difficultyButtons = [...root.querySelectorAll('[data-difficulty]')];
        this.soundButton = root.querySelector('[data-sound-toggle]');
        this.fullscreenButton = root.querySelector('[data-fullscreen]');

        this.mode = 'cpu';
        this.difficulty = 'classic';
        this.state = 'idle';
        this.stateBeforePause = 'playing';

        this.leftScore = 0;
        this.rightScore = 0;
        this.rally = 0;
        this.elapsed = 0;
        this.countdown = 0;
        this.serveDirection = 1;

        this.keys = new Set();
        this.pointerDown = false;
        this.pointerTargets = { left: null, right: null };

        this.leftPaddle = {
            x: PADDLE.margin,
            y: (HEIGHT - PADDLE.height) / 2,
            width: PADDLE.width,
            height: PADDLE.height,
        };
        this.rightPaddle = {
            x: WIDTH - PADDLE.margin - PADDLE.width,
            y: (HEIGHT - PADDLE.height) / 2,
            width: PADDLE.width,
            height: PADDLE.height,
        };
        this.ball = { x: WIDTH / 2, y: HEIGHT / 2, vx: 0, vy: 0, radius: BALL.radius };

        this.particles = [];
        this.shake = 0;
        this.aiOffset = 0;
        this.aiTimer = 0;

        this.reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.audio = null;
        this.soundEnabled = localStorage.getItem('neon-pong:sound') !== 'off';
        this.lastTimestamp = 0;

        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        this.loop = this.loop.bind(this);

        this.setupCanvas();
        this.bindEvents();
        this.updateModeUI();
        this.updateDifficultyUI();
        this.updateSoundUI();
        this.resetBoard();
        this.showIdle();

        requestAnimationFrame(this.loop);
    }

    setupCanvas() {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const targetWidth = Math.round(WIDTH * dpr);
        const targetHeight = Math.round(HEIGHT * dpr);

        if (this.canvas.width !== targetWidth || this.canvas.height !== targetHeight) {
            this.canvas.width = targetWidth;
            this.canvas.height = targetHeight;
        }

        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    bindEvents() {
        window.addEventListener('keydown', (event) => this.handleKeyDown(event));
        window.addEventListener('keyup', (event) => this.keys.delete(event.key.toLowerCase()));

        window.addEventListener('blur', () => {
            this.keys.clear();
            this.pointerDown = false;
            if (this.state === 'playing' || this.state === 'countdown') {
                this.pause();
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden && (this.state === 'playing' || this.state === 'countdown')) {
                this.pause();
            }
        });

        window.addEventListener('resize', () => this.setupCanvas());

        this.overlay.addEventListener('click', (event) => {
            const button = event.target.closest('[data-action]');
            if (button) {
                this.handleAction(button.dataset.action);
            }
        });

        this.modeButtons.forEach((button) => {
            button.addEventListener('click', () => this.setMode(button.dataset.mode));
        });

        this.difficultyButtons.forEach((button) => {
            button.addEventListener('click', () => this.setDifficulty(button.dataset.difficulty));
        });

        this.soundButton?.addEventListener('click', () => this.toggleSound());

        this.fullscreenButton?.addEventListener('click', () => {
            if (document.fullscreenElement) {
                document.exitFullscreen?.();
            } else {
                this.root.requestFullscreen?.().catch(() => {});
            }
        });

        document.addEventListener('fullscreenchange', () => {
            if (!this.fullscreenButton) {
                return;
            }
            this.fullscreenButton.textContent = document.fullscreenElement ? 'Exit fullscreen' : 'Fullscreen';
            this.setupCanvas();
        });

        this.canvas.addEventListener('pointerdown', (event) => {
            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }
            this.pointerDown = true;
            this.canvas.setPointerCapture?.(event.pointerId);
            this.movePointer(event);
            this.unlockAudio();
        });

        this.canvas.addEventListener('pointermove', (event) => {
            if (this.pointerDown) {
                this.movePointer(event);
            }
        });

        this.canvas.addEventListener('pointerup', () => {
            this.pointerDown = false;
        });

        this.canvas.addEventListener('pointercancel', () => {
            this.pointerDown = false;
        });
    }

    handleKeyDown(event) {
        const tag = (event.target?.tagName || '').toLowerCase();
        const typing = ['input', 'textarea', 'select'].includes(tag);
        const interactive = typing || ['button', 'a'].includes(tag);
        const key = event.key.toLowerCase();
        const isSpace = key === ' ' || key === 'spacebar' || event.code === 'Space';

        if (typing) {
            return;
        }

        if (interactive && (isSpace || key === 'enter')) {
            return;
        }

        if (isSpace || key === 'escape') {
            event.preventDefault();
            this.unlockAudio();

            if (this.state === 'idle' || this.state === 'over') {
                this.startMatch();
            } else if (this.state === 'paused') {
                this.resume();
            } else {
                this.pause();
            }
            return;
        }

        if (key === 'enter' && (this.state === 'idle' || this.state === 'over')) {
            this.startMatch();
            return;
        }

        if (key === 'arrowup' || key === 'arrowdown') {
            if (this.state !== 'idle') {
                event.preventDefault();
            }
            this.unlockAudio();
        }

        this.keys.add(key);
    }

    movePointer(event) {
        const rect = this.canvas.getBoundingClientRect();

        if (!rect.width || !rect.height) {
            return;
        }

        const x = ((event.clientX - rect.left) / rect.width) * WIDTH;
        const y = ((event.clientY - rect.top) / rect.height) * HEIGHT;
        const side = this.mode === 'local' && x > WIDTH / 2 ? 'right' : 'left';
        const height = side === 'left' ? this.leftPaddle.height : this.rightPaddle.height;

        this.pointerTargets[side] = clamp(y, height / 2, HEIGHT - height / 2);
    }

    updatePaddles(dt) {
        this.updateLeftPaddle(dt);

        if (this.mode === 'local') {
            this.updateRightPaddle(dt);
        } else {
            this.updateCpuPaddle(dt);
        }
    }

    updateLeftPaddle(dt) {
        if (this.pointerTargets.left !== null) {
            this.movePaddleTowards(this.leftPaddle, this.pointerTargets.left, PADDLE.speed, dt);

            if (Math.abs(this.pointerTargets.left - this.paddleCenter(this.leftPaddle)) < 4) {
                this.pointerTargets.left = null;
            }
            return;
        }

        let direction = 0;

        if (this.keys.has('w')) {
            direction -= 1;
        }
        if (this.keys.has('s')) {
            direction += 1;
        }
        if (this.mode === 'cpu') {
            if (this.keys.has('arrowup')) {
                direction -= 1;
            }
            if (this.keys.has('arrowdown')) {
                direction += 1;
            }
        }

        this.movePaddleBy(this.leftPaddle, Math.sign(direction) * PADDLE.speed * dt);
    }

    updateRightPaddle(dt) {
        if (this.pointerTargets.right !== null) {
            this.movePaddleTowards(this.rightPaddle, this.pointerTargets.right, PADDLE.speed, dt);

            if (Math.abs(this.pointerTargets.right - this.paddleCenter(this.rightPaddle)) < 4) {
                this.pointerTargets.right = null;
            }
            return;
        }

        let direction = 0;

        if (this.keys.has('arrowup')) {
            direction -= 1;
        }
        if (this.keys.has('arrowdown')) {
            direction += 1;
        }

        this.movePaddleBy(this.rightPaddle, Math.sign(direction) * PADDLE.speed * dt);
    }

    updateCpuPaddle(dt) {
        const config = DIFFICULTIES[this.difficulty];

        this.aiTimer -= dt;
        if (this.aiTimer <= 0) {
            this.aiOffset = random(-config.error, config.error);
            this.aiTimer = random(0.35, 0.9);
        }

        let target = HEIGHT / 2;

        if (this.ball.vx > 0) {
            target = this.difficulty === 'insane' ? this.predictBallY() : this.ball.y;
            target += this.aiOffset;
        }

        this.movePaddleTowards(this.rightPaddle, clamp(target, 0, HEIGHT), config.paddleSpeed, dt);
    }

    predictBallY() {
        const ball = this.ball;
        const paddleFace = this.rightPaddle.x - ball.radius;

        if (ball.vx <= 0 || ball.x >= paddleFace) {
            return HEIGHT / 2;
        }

        const travelTime = (paddleFace - ball.x) / ball.vx;
        const span = HEIGHT - ball.radius * 2;
        const range = span * 2;
        let offset = (ball.y - ball.radius + ball.vy * travelTime) % range;

        if (offset < 0) {
            offset += range;
        }

        const y = offset > span ? range - offset : offset;

        return y + ball.radius;
    }

    paddleCenter(paddle) {
        return paddle.y + paddle.height / 2;
    }

    movePaddleBy(paddle, delta) {
        paddle.y = clamp(paddle.y + delta, 0, HEIGHT - paddle.height);
    }

    movePaddleTowards(paddle, targetCenter, speed, dt) {
        const diff = targetCenter - this.paddleCenter(paddle);
        const step = speed * dt;

        if (Math.abs(diff) <= step) {
            paddle.y = clamp(targetCenter - paddle.height / 2, 0, HEIGHT - paddle.height);
        } else {
            this.movePaddleBy(paddle, Math.sign(diff) * step);
        }
    }

    serve() {
        const config = DIFFICULTIES[this.difficulty];
        const angle = random(-0.42, 0.42);
        const speed = config.ballSpeed + Math.min(this.elapsed * 1.5, 80);

        this.ball.vx = Math.cos(angle) * speed * this.serveDirection;
        this.ball.vy = Math.sin(angle) * speed;
        this.state = 'playing';
        this.updateStatus();
        this.beep(320, 0.05);
        this.liveStatus('Serve.');
    }

    resetBall() {
        this.ball.x = WIDTH / 2;
        this.ball.y = HEIGHT / 2;
        this.ball.vx = 0;
        this.ball.vy = 0;
    }

    update(dt) {
        this.updatePaddles(dt);

        const speed = Math.hypot(this.ball.vx, this.ball.vy);
        const steps = Math.max(1, Math.ceil((speed * dt) / BALL.step));
        const subStep = dt / steps;

        for (let step = 0; step < steps; step += 1) {
            if (this.state !== 'playing') {
                break;
            }
            this.stepBall(subStep);
        }
    }

    stepBall(dt) {
        const ball = this.ball;

        ball.x += ball.vx * dt;
        ball.y += ball.vy * dt;

        if (ball.y - ball.radius <= 0) {
            ball.y = ball.radius;
            ball.vy = Math.abs(ball.vy);
            this.onWallHit();
        } else if (ball.y + ball.radius >= HEIGHT) {
            ball.y = HEIGHT - ball.radius;
            ball.vy = -Math.abs(ball.vy);
            this.onWallHit();
        }

        if (ball.vx < 0 && ball.x >= this.leftPaddle.x && this.hitsPaddle(this.leftPaddle)) {
            this.bounceOffPaddle(this.leftPaddle, 1);
        } else if (ball.vx > 0 && ball.x <= this.rightPaddle.x + this.rightPaddle.width && this.hitsPaddle(this.rightPaddle)) {
            this.bounceOffPaddle(this.rightPaddle, -1);
        }

        if (ball.x < -ball.radius) {
            this.scorePoint('right');
        } else if (ball.x > WIDTH + ball.radius) {
            this.scorePoint('left');
        }
    }

    hitsPaddle(paddle) {
        const nearestX = clamp(this.ball.x, paddle.x, paddle.x + paddle.width);
        const nearestY = clamp(this.ball.y, paddle.y, paddle.y + paddle.height);
        const dx = this.ball.x - nearestX;
        const dy = this.ball.y - nearestY;

        return dx * dx + dy * dy <= this.ball.radius * this.ball.radius;
    }

    bounceOffPaddle(paddle, direction) {
        const ball = this.ball;
        const relative = clamp((ball.y - this.paddleCenter(paddle)) / (paddle.height / 2), -1, 1);
        const currentSpeed = Math.hypot(ball.vx, ball.vy);
        const speed = Math.min(BALL.maxSpeed, Math.max(currentSpeed * BALL.speedUp, currentSpeed + 18));
        const angle = relative * 0.92;

        ball.vx = Math.cos(angle) * speed * direction;
        ball.vy = Math.sin(angle) * speed;
        ball.x = direction > 0
            ? paddle.x + paddle.width + ball.radius + 0.5
            : paddle.x - ball.radius - 0.5;

        this.rally += 1;
        this.shake = Math.min(9, this.shake + 3.5);
        this.aiTimer = 0;
        this.spawnParticles(ball.x, ball.y, direction > 0 ? '#22d3ee' : '#f472b6');
        this.beep(direction > 0 ? 520 : 380, 0.05, 'square');
        this.updateStatus();
    }

    onWallHit() {
        this.shake = Math.min(6, this.shake + 1.5);
        this.beep(180, 0.03, 'triangle', 0.03);
    }

    leftName() {
        return this.mode === 'cpu' ? 'You' : 'P1';
    }

    rightName() {
        return this.mode === 'cpu' ? 'CPU' : 'P2';
    }

    durationSeconds() {
        return Math.max(1, Math.round(this.elapsed));
    }

    scorePoint(side) {
        if (this.state !== 'playing') {
            return;
        }

        if (side === 'left') {
            this.leftScore += 1;
        } else {
            this.rightScore += 1;
        }

        this.rally = 0;
        this.shake = 8;
        this.setScores();
        this.spawnParticles(side === 'left' ? WIDTH - 12 : 12, this.ball.y, side === 'left' ? '#22d3ee' : '#f472b6');
        this.beep(side === 'left' ? 660 : 160, 0.18, 'sawtooth', 0.05);
        this.updateStatus();

        if (this.leftScore >= WINNING_SCORE || this.rightScore >= WINNING_SCORE) {
            this.endMatch();
            return;
        }

        this.state = 'countdown';
        this.countdown = 1.1;
        this.serveDirection = side === 'left' ? 1 : -1;
        this.pointerTargets.left = null;
        this.pointerTargets.right = null;
        this.resetBall();
        this.updateStatus();
        this.liveStatus(`Point for ${side === 'left' ? this.leftName() : this.rightName()}. ${this.leftScore} to ${this.rightScore}.`);
    }

    endMatch() {
        this.state = 'over';
        this.updateStatus();

        const playerWon = this.leftScore > this.rightScore;
        const duration = this.durationSeconds();
        const label = DIFFICULTIES[this.difficulty].label;

        if (this.mode === 'cpu' && playerWon) {
            this.showOverlay({
                kicker: 'Match won',
                title: 'You beat the machine',
                copy: `${this.leftScore}\u2013${this.rightScore} on ${label} in ${formatDuration(duration)}. Post your handle to lock the time into the hall of fame.`,
                actions: [{ label: 'Play again', action: 'restart', variant: 'primary' }],
                extra: this.buildScoreForm(duration),
            });
            this.playWinJingle();
            this.liveStatus(`You win ${this.leftScore} to ${this.rightScore} in ${formatDuration(duration)}.`);
            return;
        }

        if (this.mode === 'cpu') {
            this.showOverlay({
                kicker: 'Match over',
                title: 'The CPU takes it',
                copy: `${this.leftScore}\u2013${this.rightScore}. The machine found the gaps this time \u2014 drop the difficulty or run it back.`,
                actions: [
                    { label: 'Rematch', action: 'restart', variant: 'primary' },
                    { label: 'View the board', action: 'leaderboard', variant: 'ghost' },
                ],
            });
            this.beep(170, 0.35, 'sawtooth', 0.05);
            this.liveStatus(`CPU wins ${this.rightScore} to ${this.leftScore}.`);
            return;
        }

        const winner = playerWon ? 'Player 1' : 'Player 2';
        const high = Math.max(this.leftScore, this.rightScore);
        const low = Math.min(this.leftScore, this.rightScore);

        this.showOverlay({
            kicker: 'Local match over',
            title: `${winner} wins`,
            copy: `${high}\u2013${low}. Local bragging rights only \u2014 only CPU victories make the hall of fame.`,
            actions: [{ label: 'Run it back', action: 'restart', variant: 'primary' }],
        });
        this.playWinJingle();
        this.liveStatus(`${winner} wins ${high} to ${low}.`);
    }

    startMatch() {
        this.unlockAudio();
        this.resetBoard();
        this.state = 'countdown';
        this.countdown = 1.2;
        this.serveDirection = Math.random() < 0.5 ? -1 : 1;
        this.overlay.hidden = true;
        this.overlayExtra.replaceChildren();
        this.updateStatus();
        this.beep(440, 0.08);
        this.liveStatus('Match started. First to seven.');
    }

    resetBoard() {
        this.leftScore = 0;
        this.rightScore = 0;
        this.rally = 0;
        this.elapsed = 0;
        this.shake = 0;
        this.aiOffset = 0;
        this.aiTimer = 0;
        this.particles = [];
        this.leftPaddle.y = (HEIGHT - PADDLE.height) / 2;
        this.rightPaddle.y = (HEIGHT - PADDLE.height) / 2;
        this.pointerTargets = { left: null, right: null };
        this.resetBall();
        this.setScores();
    }

    setScores() {
        this.leftScoreEl.textContent = String(this.leftScore);
        this.rightScoreEl.textContent = String(this.rightScore);
    }

    pause() {
        if (this.state !== 'playing' && this.state !== 'countdown') {
            return;
        }

        this.stateBeforePause = this.state;
        this.state = 'paused';
        this.keys.clear();
        this.updateStatus();
        this.showOverlay({
            kicker: 'Paused',
            title: 'Intermission',
            copy: 'Take a breath. The ball is not going anywhere.',
            actions: [
                { label: 'Resume', action: 'resume', variant: 'primary' },
                { label: 'Restart', action: 'restart', variant: 'ghost' },
            ],
        });
    }

    resume() {
        if (this.state !== 'paused') {
            return;
        }

        this.state = this.stateBeforePause;
        this.overlay.hidden = true;
        this.overlayExtra.replaceChildren();
        this.updateStatus();
    }

    handleAction(action) {
        if (action === 'start' || action === 'restart') {
            this.startMatch();
        } else if (action === 'resume') {
            this.resume();
        } else if (action === 'pause') {
            this.pause();
        } else if (action === 'leaderboard') {
            window.location.assign(this.root.dataset.leaderboardUrl);
        }
    }

    setMode(mode) {
        if (!['cpu', 'local'].includes(mode) || this.mode === mode) {
            return;
        }

        this.mode = mode;
        this.updateModeUI();
        this.resetBoard();
        this.state = 'idle';
        this.showIdle();
        this.beep(520, 0.04);
    }

    setDifficulty(difficulty) {
        if (!DIFFICULTIES[difficulty] || this.difficulty === difficulty) {
            return;
        }

        this.difficulty = difficulty;
        this.updateDifficultyUI();

        if (this.mode === 'cpu') {
            this.resetBoard();
            this.state = 'idle';
            this.showIdle();
        }

        this.beep(600, 0.04);
    }

    updateModeUI() {
        this.modeButtons.forEach((button) => {
            const active = button.dataset.mode === this.mode;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (this.leftLabelEl) {
            this.leftLabelEl.textContent = this.leftName();
        }
        if (this.rightLabelEl) {
            this.rightLabelEl.textContent = this.rightName();
        }
    }

    updateDifficultyUI() {
        this.difficultyButtons.forEach((button) => {
            const active = button.dataset.difficulty === this.difficulty;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    updateStatus() {
        let text = 'First to 7';

        if (this.state === 'countdown') {
            text = 'Serving';
        } else if (this.state === 'paused') {
            text = 'Paused';
        } else if (this.state === 'over') {
            text = 'Match over';
        } else if (this.state === 'playing') {
            const leaderScore = Math.max(this.leftScore, this.rightScore);

            if (leaderScore === WINNING_SCORE - 1) {
                const leader = this.leftScore > this.rightScore ? this.leftName() : this.rightName();
                text = `${leader} match point`;
            } else {
                text = `Rally ${this.rally}`;
            }
        }

        if (this.statusEl) {
            this.statusEl.textContent = text;
        }
    }

    showOverlay({ kicker, title, copy, actions = [], extra = null }) {
        this.overlayKicker.textContent = kicker;
        this.overlayTitle.textContent = title;
        this.overlayCopy.textContent = copy;
        this.overlayActions.replaceChildren();

        actions.forEach((action) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = action.variant === 'ghost' ? 'btn btn-ghost' : 'btn btn-primary';
            button.dataset.action = action.action;
            button.textContent = action.label;
            this.overlayActions.append(button);
        });

        this.overlayExtra.replaceChildren();
        if (extra) {
            this.overlayExtra.append(extra);
        }

        this.overlay.hidden = false;
    }

    showIdle() {
        const isCpu = this.mode === 'cpu';
        const label = DIFFICULTIES[this.difficulty].label;

        this.showOverlay({
            kicker: 'Ready player one',
            title: 'NEON PONG',
            copy: isCpu
                ? `First to ${WINNING_SCORE}. ${label} CPU is waiting. Press Space or hit start.`
                : `Local 2P. P1 uses W/S, P2 uses the arrows. First to ${WINNING_SCORE} takes the set.`,
            actions: [{ label: 'Start match', action: 'start', variant: 'primary' }],
        });

        this.updateStatus();
    }

    liveStatus(message) {
        if (this.liveStatusEl) {
            this.liveStatusEl.textContent = message;
        }
    }

    buildScoreForm(duration) {
        if (!this.scoreTemplate) {
            return null;
        }

        const form = this.scoreTemplate.content.firstElementChild?.cloneNode(true);

        if (!form) {
            return null;
        }

        form.querySelector('[name="score"]').value = String(this.leftScore);
        form.querySelector('[name="difficulty"]').value = this.difficulty;
        form.querySelector('[name="duration_seconds"]').value = String(duration);

        const nameInput = form.querySelector('[name="player_name"]');
        nameInput.value = localStorage.getItem('neon-pong:handle') || '';

        const feedback = form.querySelector('[data-score-feedback]');
        const submit = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (form.dataset.pending === 'true') {
                return;
            }

            form.dataset.pending = 'true';
            submit.disabled = true;
            submit.textContent = 'Posting\u2026';
            feedback.textContent = '';
            feedback.classList.remove('is-success');

            try {
                const response = await fetch(this.root.dataset.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                    throw new Error(errors[0] || payload.message || 'Could not save that score. Try again.');
                }

                localStorage.setItem('neon-pong:handle', nameInput.value.trim());
                form.replaceWith(this.buildReceipt(payload.rank, payload.entry));
                this.beep(880, 0.1);
                this.beep(1180, 0.12);
            } catch (error) {
                feedback.textContent = error.message;
                form.dataset.pending = 'false';
                submit.disabled = false;
                submit.textContent = 'Post my time';
            }
        });

        return form;
    }

    buildReceipt(rank, entry = {}) {
        const wrapper = document.createElement('div');
        wrapper.className = 'score-receipt';

        const kicker = document.createElement('p');
        kicker.className = 'receipt-kicker';
        kicker.textContent = `Rank #${Number(rank) || 1} locked`;

        const name = document.createElement('p');
        name.className = 'receipt-name';
        name.textContent = entry.player_name || 'Player';

        const meta = document.createElement('p');
        meta.className = 'receipt-meta';
        const difficulty = DIFFICULTIES[entry.difficulty]?.label || 'Classic';
        meta.textContent = `${entry.score ?? this.leftScore} pts \u00b7 ${formatDuration(entry.duration_seconds || 0)} \u00b7 ${difficulty}`;

        const link = document.createElement('a');
        link.className = 'btn btn-ghost btn-sm';
        link.href = this.root.dataset.leaderboardUrl;
        link.textContent = 'See the board';

        wrapper.append(kicker, name, meta, link);

        return wrapper;
    }

    unlockAudio() {
        if (!this.audio) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;

            if (AudioContextClass) {
                this.audio = new AudioContextClass();
            }
        }

        if (this.audio?.state === 'suspended') {
            this.audio.resume().catch(() => {});
        }
    }

    beep(frequency, duration = 0.06, type = 'square', volume = 0.045) {
        if (!this.soundEnabled) {
            return;
        }

        this.unlockAudio();

        if (!this.audio) {
            return;
        }

        const now = this.audio.currentTime;
        const oscillator = this.audio.createOscillator();
        const gain = this.audio.createGain();

        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, now);
        gain.gain.setValueAtTime(Math.max(volume, 0.0001), now);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);
        oscillator.connect(gain);
        gain.connect(this.audio.destination);
        oscillator.start(now);
        oscillator.stop(now + duration);
    }

    playWinJingle() {
        [523.25, 659.25, 783.99, 1046.5].forEach((frequency, index) => {
            window.setTimeout(() => this.beep(frequency, 0.12, 'triangle', 0.05), index * 90);
        });
    }

    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('neon-pong:sound', this.soundEnabled ? 'on' : 'off');
        this.updateSoundUI();

        if (this.soundEnabled) {
            this.beep(720, 0.06);
        }
    }

    updateSoundUI() {
        if (!this.soundButton) {
            return;
        }

        this.soundButton.textContent = this.soundEnabled ? 'SFX on' : 'SFX off';
        this.soundButton.setAttribute('aria-pressed', this.soundEnabled ? 'true' : 'false');
    }

    spawnParticles(x, y, color) {
        if (this.reduceMotion) {
            return;
        }

        for (let index = 0; index < 9; index += 1) {
            const angle = random(0, Math.PI * 2);
            const speed = random(40, 230);

            this.particles.push({
                x,
                y,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                life: 1,
                decay: random(1.6, 3.2),
                size: random(1.5, 3.2),
                color,
            });
        }
    }

    updateParticles(dt) {
        for (let index = this.particles.length - 1; index >= 0; index -= 1) {
            const particle = this.particles[index];

            particle.life -= particle.decay * dt;

            if (particle.life <= 0) {
                this.particles.splice(index, 1);
                continue;
            }

            particle.x += particle.vx * dt;
            particle.y += particle.vy * dt;
            particle.vy += 90 * dt;
        }
    }

    drawParticles() {
        const ctx = this.ctx;

        ctx.save();
        this.particles.forEach((particle) => {
            ctx.globalAlpha = Math.max(0, particle.life);
            ctx.fillStyle = particle.color;
            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.restore();
    }

    drawPaddle(paddle, color) {
        const ctx = this.ctx;

        ctx.save();
        ctx.shadowColor = color;
        ctx.shadowBlur = 26;
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.roundRect(paddle.x, paddle.y, paddle.width, paddle.height, 6);
        ctx.fill();

        ctx.shadowBlur = 0;
        ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
        ctx.beginPath();
        ctx.roundRect(paddle.x + 3, paddle.y + 4, paddle.width - 6, paddle.height - 8, 4);
        ctx.fill();
        ctx.restore();
    }

    drawBall() {
        const ctx = this.ctx;
        const ball = this.ball;

        ctx.save();
        ctx.shadowColor = '#67e8f9';
        ctx.shadowBlur = 30;
        ctx.fillStyle = '#f8fafc';
        ctx.beginPath();
        ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
        ctx.fill();

        ctx.shadowBlur = 0;
        ctx.lineWidth = 2;
        ctx.strokeStyle = 'rgba(34, 211, 238, 0.85)';
        ctx.stroke();
        ctx.restore();
    }

    draw() {
        const ctx = this.ctx;
        const shake = this.reduceMotion ? 0 : this.shake;

        ctx.save();

        if (shake > 0.3) {
            ctx.translate(random(-shake, shake), random(-shake, shake));
        }

        const background = ctx.createLinearGradient(0, 0, 0, HEIGHT);
        background.addColorStop(0, '#040713');
        background.addColorStop(1, '#02040c');
        ctx.fillStyle = background;
        ctx.fillRect(-30, -30, WIDTH + 60, HEIGHT + 60);

        ctx.strokeStyle = 'rgba(56, 189, 248, 0.045)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        for (let x = 0; x <= WIDTH; x += 64) {
            ctx.moveTo(x, 0);
            ctx.lineTo(x, HEIGHT);
        }
        for (let y = 0; y <= HEIGHT; y += 64) {
            ctx.moveTo(0, y);
            ctx.lineTo(WIDTH, y);
        }
        ctx.stroke();

        ctx.save();
        ctx.setLineDash([16, 26]);
        ctx.lineDashOffset = -((this.elapsed * 36) % 42);
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.2)';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(WIDTH / 2, 10);
        ctx.lineTo(WIDTH / 2, HEIGHT - 10);
        ctx.stroke();
        ctx.restore();

        this.drawPaddle(this.leftPaddle, '#22d3ee');
        this.drawPaddle(this.rightPaddle, '#f472b6');
        this.drawBall();
        this.drawParticles();

        ctx.restore();
    }

    loop(timestamp) {
        if (!this.lastTimestamp) {
            this.lastTimestamp = timestamp;
        }

        const dt = Math.min(0.04, Math.max(0, (timestamp - this.lastTimestamp) / 1000));
        this.lastTimestamp = timestamp;

        if (this.state === 'countdown') {
            this.elapsed += dt;
            this.updatePaddles(dt);
            this.countdown -= dt;

            if (this.countdown <= 0) {
                this.serve();
            }
        } else if (this.state === 'playing') {
            this.elapsed += dt;
            this.update(dt);
        }

        this.updateParticles(dt);
        this.shake = Math.max(0, this.shake - 30 * dt);
        this.draw();
        requestAnimationFrame(this.loop);
    }
}

const boot = () => {
    document.querySelectorAll('[data-pong]').forEach((root) => new PongGame(root));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { PongGame };
