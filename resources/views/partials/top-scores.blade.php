@php
    $difficultyLabels = [
        'chill' => 'Chill',
        'classic' => 'Classic',
        'insane' => 'Insane',
    ];
@endphp

@if ($scores->isEmpty())
    <p class="text-sm text-slate-500">No wins yet. Be the first name on the board.</p>
@else
    <ol class="top-score-list">
        @foreach ($scores as $index => $score)
            <li>
                <span @class(['top-rank', 'rank-gold' => $index === 0, 'rank-silver' => $index === 1, 'rank-bronze' => $index === 2])>
                    {{ $index + 1 }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-slate-100">{{ $score->player_name }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $difficultyLabels[$score->difficulty] ?? $score->difficulty }}</span>
                </span>
                <span class="font-mono text-sm text-cyan-200">
                    {{ sprintf('%d:%02d', intdiv($score->duration_seconds, 60), $score->duration_seconds % 60) }}
                </span>
            </li>
        @endforeach
    </ol>
@endif
