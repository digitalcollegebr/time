@extends($layout)

@section('content')
    <style>
        .lace-wrap{
            --lace-bg:#faf8f9; --lace-card:#ffffff; --lace-ink:#2b2127; --lace-muted:#8b7c83;
            --lace-brand:#a4123f; --lace-line:#eee3e8;
            --lace-shadow:0 1px 2px rgba(60,20,35,.06), 0 8px 24px rgba(60,20,35,.07);
            --lace-hw:88px; --lace-hh:102px;
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,system-ui,sans-serif;
            background:var(--lace-bg); color:var(--lace-ink);
            padding:24px clamp(14px,2.5vw,34px) 40px; border-radius:12px;
            -webkit-font-smoothing:antialiased;
        }
        @media (prefers-color-scheme:dark){
            .lace-wrap{ --lace-bg:#161114; --lace-card:#1f171b; --lace-ink:#f0e6ea; --lace-muted:#a08d96;
                --lace-brand:#e0567e; --lace-line:#332631;
                --lace-shadow:0 1px 2px rgba(0,0,0,.4), 0 8px 24px rgba(0,0,0,.35); }
        }
        .lace-wrap *{ box-sizing:border-box; }

        /* ===== Header: decision layer ===== */
        .lace-head{ display:flex; align-items:stretch; justify-content:space-between; gap:18px; flex-wrap:wrap; }
        .lace-id .lace-eyebrow{ margin:0 0 4px; color:var(--lace-muted); font-size:12px;
            text-transform:uppercase; letter-spacing:2px; font-weight:700; }
        .lace-id h1{ margin:0; font-size:28px; font-weight:800; letter-spacing:.4px; color:var(--lace-ink); }
        .lace-id .lace-meta{ margin:6px 0 0; color:var(--lace-muted); font-size:12.5px; font-weight:500; }
        .lace-kpis{ display:flex; gap:12px; align-items:stretch; flex-wrap:wrap; }
        .lace-kpi{
            background:var(--lace-card); border:1px solid var(--lace-line); border-radius:14px;
            box-shadow:var(--lace-shadow); padding:12px 16px; min-width:158px;
            display:flex; flex-direction:column; justify-content:center; gap:6px;
        }
        .lace-kpi .lace-lbl{ font-size:10.5px; font-weight:700; text-transform:uppercase;
            letter-spacing:1.2px; color:var(--lace-muted); }
        .lace-kpi .lace-num{ font-size:22px; font-weight:800; font-variant-numeric:tabular-nums; line-height:1; }
        .lace-kpi .lace-bar{ height:6px; border-radius:3px; background:var(--lace-line); overflow:hidden; }
        .lace-kpi .lace-bar i{ display:block; height:100%; border-radius:3px; }
        .lace-kpi.lace-overall{ flex-direction:row; align-items:center; gap:14px; padding:12px 18px; }
        .lace-ring{ width:64px; height:64px; flex:none; }
        .lace-kpi.lace-overall .lace-num{ font-size:26px; }

        /* ===== Context strip ===== */
        .lace-ctx{
            margin:20px 0 14px; display:flex; align-items:center; justify-content:center; gap:14px;
            flex-wrap:wrap; color:var(--lace-muted); font-size:11.5px; font-weight:600;
        }
        .lace-ctx .lace-flow{ color:var(--lace-brand); font-weight:700; letter-spacing:.3px; }
        .lace-ctx .lace-chips{ display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .lace-chip{
            padding:5px 12px; border-radius:999px; background:var(--lace-card);
            border:1px solid var(--lace-line); font-size:11.5px; font-weight:600;
            color:var(--lace-muted); white-space:nowrap;
        }
        .lace-chip.lace-key{ background:var(--lace-brand); border-color:var(--lace-brand); color:#fff; font-weight:700; }

        /* ===== Nuclei panels (native landscape) ===== */
        .lace-board{ display:grid; grid-template-columns:1fr 86px 1.12fr 86px 1fr; align-items:stretch; }
        .lace-panel{
            background:var(--lace-card); border:1px solid var(--lace-line); border-radius:18px;
            box-shadow:var(--lace-shadow); overflow:hidden; display:flex; flex-direction:column;
        }
        .lace-panel .lace-phead{ padding:13px 18px 11px; border-bottom:1px solid var(--lace-line); }
        .lace-panel .lace-phead h2{ margin:0; font-size:14.5px; font-weight:800; letter-spacing:.4px; color:var(--lace-brand); }
        .lace-panel .lace-phead .lace-psub{ margin:2px 0 8px; font-size:11.5px; color:var(--lace-muted); font-weight:500; }
        .lace-panel .lace-phead .lace-pbar{ height:6px; border-radius:3px; background:var(--lace-line); overflow:hidden; }
        .lace-panel .lace-phead .lace-pbar i{ display:block; height:100%; border-radius:3px; }
        .lace-panel.lace-hero .lace-phead{ background:var(--lace-brand); border-bottom-color:var(--lace-brand); }
        .lace-panel.lace-hero .lace-phead h2{ color:#fff; }
        .lace-panel.lace-hero .lace-phead .lace-psub{ color:rgba(255,255,255,.78); }
        .lace-panel.lace-hero .lace-phead .lace-pbar{ background:rgba(255,255,255,.25); }
        .lace-panel .lace-pbody{ flex:1; display:flex; align-items:center; justify-content:center; padding:20px 10px 22px; }

        /* gutters with cycle connectors */
        .lace-gut{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:26px;
            position:relative; z-index:2; }
        .lace-gut .lace-lbl{
            font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.8px;
            color:var(--lace-brand); white-space:nowrap; display:flex; align-items:center; gap:4px;
            background:var(--lace-bg); border:1px solid var(--lace-line); border-radius:999px; padding:5px 11px;
        }
        .lace-gut .lace-lbl small{ font-size:13px; line-height:1; }

        /* ===== Hive ===== */
        .lace-hive{ display:inline-flex; flex-direction:column; align-items:center; }
        .lace-hex-row{ display:flex; justify-content:center; }
        .lace-hex-row + .lace-hex-row{ margin-top:calc(var(--lace-hh) * -0.252); }
        .lace-hex{
            width:var(--lace-hw); height:var(--lace-hh); margin:0 2px; position:relative; border:0;
            clip-path:polygon(50% 0%,100% 25%,100% 75%,50% 100%,0% 75%,0% 25%);
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            text-align:center; color:#fff !important; padding:0 10px; cursor:pointer;
            transition:transform .14s ease; text-decoration:none;
        }
        .lace-hex:hover{ transform:scale(1.06); z-index:3; color:#fff; text-decoration:none; }
        .lace-hex:focus-visible{ outline:3px solid var(--lace-brand); outline-offset:-3px; }
        .lace-hex .lace-n{ font-size:10.5px; font-weight:600; line-height:1.12; text-shadow:0 1px 2px rgba(0,0,0,.38); }
        .lace-hex .lace-s{ margin-top:3px; font-size:15.5px; font-weight:800; font-variant-numeric:tabular-nums;
            text-shadow:0 1px 2px rgba(0,0,0,.38); }
        .lace-hex .lace-warn{
            position:absolute; top:9px; left:50%; transform:translateX(-50%);
            width:15px; height:15px; border-radius:50%;
            background:rgba(0,0,0,.38); color:#fff; font-size:10.5px; font-weight:800;
            display:flex; align-items:center; justify-content:center;
        }

        /* ===== Attention radar ===== */
        .lace-radar{ margin-top:22px; }
        .lace-radar h3{ margin:0 0 10px; font-size:13px; font-weight:800; letter-spacing:1.4px;
            text-transform:uppercase; color:var(--lace-muted); }
        .lace-rgrid{ display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
        .lace-rcard{
            background:var(--lace-card); border:1px solid var(--lace-line); border-radius:14px;
            box-shadow:var(--lace-shadow); padding:14px 16px; display:flex; flex-direction:column; gap:8px;
        }
        .lace-rcard .lace-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .lace-rcard .lace-name{ font-size:14px; font-weight:700; }
        .lace-rcard .lace-tag{ font-size:10.5px; font-weight:700; text-transform:uppercase;
            letter-spacing:.8px; color:var(--lace-muted); }
        .lace-rcard .lace-pill{ font-size:13px; font-weight:800; color:#fff; padding:3px 10px;
            border-radius:999px; font-variant-numeric:tabular-nums; }
        .lace-rcard .lace-bar{ height:6px; border-radius:3px; background:var(--lace-line); overflow:hidden; }
        .lace-rcard .lace-bar i{ display:block; height:100%; border-radius:3px; }
        .lace-rcard a{ font-size:12.5px; font-weight:700; color:var(--lace-brand); text-decoration:none; }
        .lace-rcard a:hover{ text-decoration:underline; }

        /* ===== Footer legend ===== */
        .lace-legend{ margin-top:20px; display:flex; align-items:center; justify-content:center; gap:12px;
            color:var(--lace-muted); font-size:12px; font-weight:600; font-variant-numeric:tabular-nums; flex-wrap:wrap; }
        .lace-legend .lace-bar{ width:240px; height:9px; border-radius:5px;
            background:linear-gradient(to right,
                hsl(6,52%,49%), hsl(38,52%,44%), hsl(72,52%,40%), hsl(110,52%,42%), hsl(140,52%,45%)); }

        @media (prefers-reduced-motion:reduce){ .lace-hex{ transition:none; } }
        @media (max-width:1250px){
            .lace-board{ grid-template-columns:1fr; gap:14px; }
            .lace-gut{ flex-direction:row; gap:28px; padding:2px 0; }
            .lace-rgrid{ grid-template-columns:1fr; }
        }
    </style>

    <div class="pageheader">
        <div class="pageicon"><i class="fa fa-fw fa-diagram-project"></i></div>
        <div class="pagetitle">
            <h5>{{ __('lace.dash.eyebrow') }}</h5>
            <h1>{{ $projectName }}</h1>
        </div>
    </div>

    <div class="maincontent">
        <div class="maincontentinner">
            <div class="lace-wrap">

                {{-- Decision layer --}}
                <div class="lace-head">
                    <div class="lace-id">
                        <p class="lace-meta">
                            {{ sprintf(__('lace.dash.meta'), $totalGoals) }}@if($lastUpdated) · {{ sprintf(__('lace.dash.updated'), $lastUpdated) }}@endif
                        </p>
                    </div>
                    <div class="lace-kpis">
                        <div class="lace-kpi lace-overall">
                            <svg class="lace-ring" viewBox="0 0 36 36" aria-hidden="true">
                                <defs>
                                    <linearGradient id="laceRingGrad" x1="0" y1="1" x2="1" y2="0">
                                        <stop offset="0" stop-color="hsl(6,52%,49%)"></stop>
                                        <stop offset=".5" stop-color="hsl(72,52%,42%)"></stop>
                                        <stop offset="1" stop-color="hsl(140,52%,45%)"></stop>
                                    </linearGradient>
                                </defs>
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--lace-line)" stroke-width="4"></circle>
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="url(#laceRingGrad)"
                                    stroke-width="4" stroke-linecap="round" transform="rotate(-90 18 18)"
                                    stroke-dasharray="97.4" stroke-dashoffset="{{ $ringOffset }}"></circle>
                            </svg>
                            <div>
                                <div class="lace-lbl">{{ __('lace.dash.overall') }}</div>
                                <div class="lace-num" style="color:{{ $overallColor }}">{{ $overall }}%</div>
                            </div>
                        </div>
                        @foreach ($nuclei as $nucleus)
                            <div class="lace-kpi">
                                <div class="lace-lbl">{{ $nucleus['title'] }}</div>
                                <div class="lace-num">{{ $nucleus['avg'] }}%</div>
                                <div class="lace-bar"><i style="width:{{ $nucleus['avg'] }}%;background:{{ $nucleus['avgColor'] }}"></i></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Context strip --}}
                <div class="lace-ctx">
                    <span class="lace-flow">{{ __('lace.dash.align') }} ⇢</span>
                    <span class="lace-chips">
                        <span class="lace-chip">{{ __('lace.dash.ctx.rd') }}</span>
                        <span class="lace-chip">{{ __('lace.dash.ctx.it') }}</span>
                        <span class="lace-chip lace-key">{{ __('lace.dash.ctx.business') }}</span>
                        <span class="lace-chip">{{ __('lace.dash.ctx.da') }}</span>
                        <span class="lace-chip">{{ __('lace.dash.ctx.other') }}</span>
                    </span>
                    <span class="lace-flow">⇠ {{ __('lace.dash.realign') }}</span>
                </div>

                {{-- Nuclei (landscape board: portfolio | estrategia | modelo) --}}
                @php
                    $byKey = collect($nuclei)->keyBy('key');
                    $order = ['portfolio', 'estrategia', 'modelo'];
                    $gutters = [
                        ['<small>←</small> ' . __('lace.dash.priorities'), __('lace.dash.value') . ' <small>→</small>'],
                        [__('lace.dash.planning') . ' <small>→</small>', '<small>←</small> ' . __('lace.dash.execution')],
                    ];
                    $boardUrl = BASE_URL . '/goalcanvas/showCanvas/' . $boardId;
                @endphp
                <div class="lace-board">
                    @foreach ($order as $i => $key)
                        @php $nucleus = $byKey[$key]; @endphp
                        <section class="lace-panel {{ $key === 'estrategia' ? 'lace-hero' : '' }}">
                            <div class="lace-phead">
                                <h2>{{ $nucleus['title'] }}</h2>
                                <p class="lace-psub">{{ $nucleus['subtitle'] }}</p>
                                <div class="lace-pbar"><i style="width:{{ $nucleus['avg'] }}%;background:{{ $nucleus['avgColor'] }}"></i></div>
                            </div>
                            <div class="lace-pbody">
                                <div class="lace-hive">
                                    @foreach ($nucleus['rows'] as $row)
                                        <div class="lace-hex-row">
                                            @foreach ($row as $goal)
                                                <a class="lace-hex" href="{{ $boardUrl }}"
                                                   style="background:{{ $goal['color'] }}"
                                                   title="{{ $goal['name'] }} — {{ $goal['score'] }}%">
                                                    <span class="lace-n">{{ $goal['name'] }}</span>
                                                    <span class="lace-s">{{ $goal['score'] }}%</span>
                                                    @if ($goal['critical'])<span class="lace-warn">!</span>@endif
                                                </a>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                        @if (isset($gutters[$i]))
                            <div class="lace-gut">
                                <span class="lace-lbl">{!! $gutters[$i][0] !!}</span>
                                <span class="lace-lbl">{!! $gutters[$i][1] !!}</span>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Attention radar --}}
                <div class="lace-radar">
                    <h3>{{ __('lace.dash.radar') }}</h3>
                    <div class="lace-rgrid">
                        @foreach ($worst as $goal)
                            <div class="lace-rcard">
                                <div class="lace-top">
                                    <span class="lace-name">{{ $goal['name'] }}</span>
                                    <span class="lace-pill" style="background:{{ $goal['color'] }}">{{ $goal['score'] }}%</span>
                                </div>
                                <span class="lace-tag">{{ $goal['nucleus'] }}</span>
                                <div class="lace-bar"><i style="width:{{ $goal['score'] }}%;background:{{ $goal['color'] }}"></i></div>
                                <a href="{{ $boardUrl }}">{{ __('lace.dash.updateProgress') }} →</a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="lace-legend">
                    <span>0%</span><span class="lace-bar"></span><span>100%</span>
                    <span style="margin-left:10px;">· {{ __('lace.dash.critical') }}</span>
                </div>

            </div>
        </div>
    </div>
@endsection
