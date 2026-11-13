@php
    $difficultyStyles = [
        'chill' => 'badge-chill',
        'classic' => 'badge-classic',
        'insane' => 'badge-insane',
    ];
    $difficultyLabels = [
        'chill' => 'Chill',
        'classic' => 'Classic',
        'insane' => 'Insane',
    ];
    $showDate = $showDate ?? false;
    $emptyMessage = $emptyMessage ?? 'No records yet. Win a match against the CPU and claim the first spot.';
@endphp

@if ($scores->isEmpty())
    <div class="empty-state">
        <span class="empty-state-icon" aria-hidden="true">&#9675;</span>
        <p>{{ $emptyMessage }}</p>
    </div>
@else
    <div class="score-table-wrap">
        <table class="score-table">
            <thead>
                <tr>
                    <th scope="col" class="w-12">Rank</th>
                    <th scope="col">Player</th>
                    <th scope="col">Difficulty</th>
                    <th scope="col" class="text-right">Time</th>
                    @if ($showDate)
                        <th scope="col" class="hidden text-right sm:table-cell">Date</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($scores as $index => $score)
                    <tr>
                        <td>
                            <span @class(['rank-badge', 'rank-gold' => $index === 0, 'rank-silver' => $index === 1, 'rank-bronze' => $index === 2])>
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td>
                            <span class="player-name">{{ $score->player_name }}</span>
                            <span class="player-points">{{ $score->score }} pts</span>
                        </td>
                        <td>
                            <span class="difficulty-badge {{ $difficultyStyles[$score->difficulty] ?? 'badge-classic' }}">
                                {{ $difficultyLabels[$score->difficulty] ?? $score->difficulty }}
                            </span>
                        </td>
                        <td class="text-right font-mono text-sm text-slate-200">
                            {{ sprintf('%d:%02d', intdiv($score->duration_seconds, 60), $score->duration_seconds % 60) }}
                        </td>
                        @if ($showDate)
                            <td class="hidden text-right text-xs text-slate-500 sm:table-cell">
                                {{ $score->created_at->format('M j, Y') }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
