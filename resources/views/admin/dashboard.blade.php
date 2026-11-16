@extends('layouts.admin.base')
@section('title', __('dashboard.title') . ' | ' . config('app.name'))

@section('css')
<style>
    :root{--dash-ink:#14213d;--dash-muted:#718096;--dash-border:#e8edf4;--dash-bg:#f5f7fb;--dash-blue:#3157d5;--dash-green:#149b78;--dash-red:#e45466}
    body{background:var(--dash-bg)!important}.dashboard-page{color:var(--dash-ink)}
    .dashboard-hero{background:linear-gradient(125deg,#172554 0%,#243b78 52%,#3157d5 100%);border-radius:22px;padding:26px 28px;color:#fff;box-shadow:0 18px 42px rgba(30,50,110,.18);margin-bottom:20px;position:relative;overflow:hidden}
    .dashboard-hero:after{content:"";position:absolute;width:330px;height:330px;border:60px solid rgba(255,255,255,.05);border-radius:50%;right:-100px;top:-170px}.hero-copy{position:relative;z-index:1}.hero-copy small{opacity:.7;font-weight:700}.hero-copy h2{font-size:1.55rem;color:#fff;margin:5px 0}.hero-copy p{opacity:.72;margin:0;font-size:.85rem}
    .filter-bar{position:relative;z-index:2;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16);padding:13px;border-radius:15px;backdrop-filter:blur(8px)}.filter-bar label{display:block;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;font-weight:800;opacity:.68;margin-bottom:5px}.filter-bar .form-select,.filter-bar .form-control{height:38px;border:0;background:#fff;border-radius:9px;font-size:.78rem}.filter-submit{height:38px;border:0;border-radius:9px;background:#fff;color:#243b78;font-weight:800;width:100%}
    .metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:13px}.metric-card{background:#fff;border:1px solid var(--dash-border);border-radius:16px;padding:16px;display:flex;align-items:center;gap:12px;color:inherit!important;text-decoration:none!important;box-shadow:0 5px 18px rgba(28,39,66,.04);transition:.2s}.metric-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(28,39,66,.09)}.metric-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:12px;font-size:1.15rem;background:#edf2ff;color:#3157d5}.metric-copy{display:flex;flex-direction:column}.metric-copy small{font-size:.7rem;color:var(--dash-muted);font-weight:700}.metric-copy strong{font-size:1.4rem;line-height:1.15}.metric-arrow{margin-left:auto;color:#b6c0cf}.metric-teal .metric-icon,.tone-teal .panel-icon{background:#e7f8f4;color:#149b78}.metric-violet .metric-icon,.tone-violet .panel-icon{background:#f0ebff;color:#7450d5}.metric-orange .metric-icon{background:#fff1e6;color:#ed7a28}.metric-green .metric-icon{background:#e9f8ee;color:#259b50}.metric-pink .metric-icon{background:#fcebf2;color:#d64e80}.metric-navy .metric-icon{background:#e9edf5;color:#243b78}.metric-amber .metric-icon{background:#fff6da;color:#bf8500}
    .dashboard-heading{display:flex;align-items:end;justify-content:space-between;margin:28px 2px 12px}.dashboard-heading span,.chart-head small,.finance-head small{font-size:.66rem;color:#3157d5;text-transform:uppercase;letter-spacing:.09em;font-weight:800}.dashboard-heading h4{font-size:1.05rem;margin:2px 0 0}.dashboard-heading>small{color:var(--dash-muted)}
    .panel{background:#fff;border:1px solid var(--dash-border);border-radius:17px;box-shadow:0 5px 18px rgba(28,39,66,.035)}.balance-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.balance-panel{padding:18px}.panel-head{display:flex;gap:11px;align-items:center;border-bottom:1px solid #eef2f6;padding-bottom:14px}.panel-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:11px;background:#edf2ff;color:#3157d5;font-size:1.05rem}.panel-head h5,.finance-head h5,.chart-head h5{font-size:.88rem;margin:0}.panel-head p{font-size:.68rem;color:var(--dash-muted);margin:3px 0 0}.currency-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px dashed #e8edf4}.currency-row:last-child{border:0}.currency-badge{background:#f3f6fa;color:#40506b;padding:5px 8px;border-radius:7px;font-size:.68rem;font-weight:900}.currency-row>div{text-align:end}.currency-row strong{display:block;font-size:.9rem}.currency-row small{font-size:.62rem;color:var(--dash-muted)}.amount-positive{color:var(--dash-green)!important}.amount-negative{color:var(--dash-red)!important}.empty-state{min-height:90px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#a0aabd;gap:5px}.empty-state i{font-size:1.35rem}.empty-inline{font-size:.72rem;color:#a0aabd;padding:14px 0}
    .position-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}.position-card{padding:17px}.position-title{display:flex;align-items:center;gap:10px;margin-bottom:7px}.position-title>span{width:38px;height:38px;display:grid;place-items:center;border-radius:11px}.position-credit .position-title>span{background:#e8f8f3;color:var(--dash-green)}.position-debit .position-title>span{background:#fdecef;color:var(--dash-red)}.position-title small{font-size:.62rem;text-transform:uppercase;color:var(--dash-muted)}.position-title h5{font-size:.9rem;margin:0}.position-row{display:flex;justify-content:space-between;padding:10px 0;border-top:1px dashed var(--dash-border);font-size:.78rem}.position-row span small{color:var(--dash-muted);margin-left:6px}.position-credit .position-row strong{color:var(--dash-green)}.position-debit .position-row strong{color:var(--dash-red)}
    .finance-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.finance-card{padding:18px}.finance-head{display:flex;align-items:center;gap:10px;margin-bottom:12px}.finance-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:11px}.finance-icon.profit{background:#e7f8f3;color:var(--dash-green)}.finance-icon.expense{background:#fdecef;color:var(--dash-red)}.finance-icon.closing{background:#edf2ff;color:#3157d5}.finance-line{display:flex;justify-content:space-between;border-top:1px dashed var(--dash-border);padding:9px 0;font-size:.8rem}.closing-value{font-size:1.65rem;font-weight:900;color:#3157d5;margin:16px 0}.closing-breakdown{display:flex;gap:8px}.closing-breakdown span{background:#f5f7fb;padding:6px 8px;border-radius:7px;font-size:.65rem;color:var(--dash-muted)}
    .chart-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px;margin-top:14px}.chart-panel{padding:18px;min-width:0}.chart-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.chart-head>span{font-size:.68rem;color:var(--dash-muted)}.chart-wrap{height:245px;position:relative}.chart-head a{font-size:.72rem;text-decoration:none}.recent-panel{padding:18px;margin-top:14px}.activity-table{width:100%;border-collapse:collapse}.activity-table th{font-size:.63rem;text-transform:uppercase;color:var(--dash-muted);letter-spacing:.06em;padding:9px;border-bottom:1px solid var(--dash-border)}.activity-table td{font-size:.73rem;padding:11px 9px;border-bottom:1px solid #f1f3f7}.activity-table td>small,.activity-table td>strong+small{display:block;color:var(--dash-muted)}.type-pill{padding:4px 7px;border-radius:7px;font-size:.64rem;font-weight:800}.type-pill.credit{background:#e7f8f3;color:var(--dash-green)}.type-pill.debit{background:#fdecef;color:var(--dash-red)}
    .dashboard-loader{position:fixed;inset:0;background:rgba(245,247,251,.65);z-index:1090;display:none;place-items:center;backdrop-filter:blur(2px)}.dashboard-loading .dashboard-loader{display:grid}.loader-box{background:#fff;border-radius:15px;padding:18px 25px;box-shadow:0 15px 45px rgba(20,33,61,.15);font-size:.8rem;font-weight:700}.loader-box .spinner-border{width:1.2rem;height:1.2rem;margin-right:8px}
    @media(max-width:1199px){.metric-grid{grid-template-columns:repeat(2,1fr)}.chart-grid{grid-template-columns:1fr 1fr}.chart-wide{grid-column:1/-1}}@media(max-width:767px){.dashboard-hero{padding:20px}.filter-bar{margin-top:15px}.metric-grid,.balance-grid,.position-grid,.finance-grid,.chart-grid{grid-template-columns:1fr}.chart-wide{grid-column:auto}.dashboard-heading{align-items:start;gap:5px;flex-direction:column}}
    /* Compact dashboard toolbar and accessible refresh action */
    .dashboard-page{max-width:1800px;margin-inline:auto}
    .dashboard-hero{padding:20px 22px;border-radius:17px;margin-bottom:14px}
    .dashboard-hero-top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:16px;position:relative;z-index:2}
    .dashboard-hero .hero-copy h2{font-size:1.35rem;margin:2px 0 3px}
    .dashboard-hero .hero-copy p{font-size:.78rem}
    .dashboard-refresh-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:39px;padding:8px 14px;background:#fff;color:#213e87;border:1px solid rgba(255,255,255,.85);border-radius:10px;font-size:.76rem;font-weight:750;line-height:1.2;box-shadow:0 4px 12px rgba(8,25,66,.15);white-space:nowrap;transition:background .15s,transform .15s,box-shadow .15s;flex-shrink:0}
    .dashboard-refresh-btn:hover{background:#edf3ff;color:#1b3478;transform:translateY(-1px);box-shadow:0 7px 16px rgba(8,25,66,.2)}
    .dashboard-refresh-btn:focus-visible,.filter-submit:focus-visible,.dashboard-filter-field :is(input,select):focus-visible{outline:3px solid #9bbcff;outline-offset:2px}
    .dashboard-refresh-btn:disabled{opacity:.75;cursor:wait;transform:none}
    .dashboard-refresh-btn i{font-size:1rem}
    .dashboard-refresh-btn.is-refreshing i{animation:dashboard-spin .9s linear infinite}
    @keyframes dashboard-spin{to{transform:rotate(360deg)}}
    .filter-bar{padding:11px 12px;border-radius:12px}
    .dashboard-filter-grid{display:grid;grid-template-columns:minmax(160px,1.5fr) minmax(145px,1.3fr) minmax(125px,1fr) minmax(125px,1fr) minmax(110px,.7fr);align-items:end;gap:10px}
    .dashboard-filter-field{min-width:0}
    .dashboard-filter-field label{font-size:.64rem;letter-spacing:.04em;margin-bottom:5px}
    .dashboard-filter-field .form-select,.dashboard-filter-field .form-control{height:39px;min-width:0;width:100%;font-size:.78rem;border:1px solid transparent}
    .dashboard-filter-field .form-control:focus,.dashboard-filter-field .form-select:focus{border-color:#99b7fc;box-shadow:0 0 0 2px rgba(153,183,252,.2)}
    .dashboard-filter-action .filter-submit{height:39px;white-space:nowrap;transition:background .15s}
    .dashboard-filter-action .filter-submit:hover{background:#e9efff}
    .metric-grid{gap:11px}.metric-card{min-width:0;padding:13px 14px;border-radius:13px;gap:10px}.metric-icon{flex:0 0 39px;width:39px;height:39px;border-radius:10px}
    .metric-copy{min-width:0}.metric-copy small{line-height:1.25}.metric-copy strong{font-size:1.25rem;margin-top:3px;font-variant-numeric:tabular-nums}
    .metric-card:focus-visible{outline:3px solid #8daaff;outline-offset:2px}
    .dashboard-heading{margin-top:22px;margin-bottom:10px}
    .balance-grid,.position-grid,.finance-grid,.chart-grid{gap:11px}
    .balance-panel,.finance-card,.position-card,.chart-panel,.recent-panel{padding:15px}
    .panel{min-width:0;border-radius:14px}
    .currency-row strong,.position-row strong,.finance-line strong,.activity-table td strong{font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
    .closing-breakdown{flex-wrap:wrap}
    @media(max-width:1050px){.dashboard-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-filter-action{grid-column:1/-1}.dashboard-filter-action .filter-submit{width:100%}}
    @media(max-width:767px){.dashboard-hero{padding:15px}.dashboard-hero-top{align-items:flex-start;gap:12px}.dashboard-refresh-btn{padding:9px;min-height:39px}.dashboard-refresh-btn .refresh-label{display:none}.dashboard-refresh-btn i{font-size:1.12rem}.dashboard-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.dashboard-filter-field:first-child,.dashboard-filter-field:nth-child(2){grid-column:1/-1}.metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.metric-card{padding:11px}.metric-icon{flex-basis:34px;width:34px;height:34px}.metric-copy strong{font-size:1.12rem}.metric-arrow{display:none}.chart-wrap{height:210px}}
    @media(max-width:400px){.metric-grid{grid-template-columns:1fr}.dashboard-hero .hero-copy h2{font-size:1.16rem}}
    @media(prefers-reduced-motion:reduce){.dashboard-refresh-btn,.metric-card{transition:none}.dashboard-refresh-btn.is-refreshing i{animation:none}}

    /* Dashboard visual system: hierarchy, restrained color, readable figures. */
    .dashboard-page{
        --dash-border:#e6ebf3;
        --dash-muted:#65738a;
        --dash-ink:#182842;
        --dash-bg:#f5f7fb;
        --dash-green:#0d9471;
        --dash-red:#dc5066;
        font-size:13px;
        line-height:1.5;
    }
    .dashboard-hero{
        background:linear-gradient(115deg,#152442 0%,#243965 57%,#304d9a 100%);
        border:1px solid rgba(255,255,255,.09);
        box-shadow:0 12px 32px rgba(27,46,91,.12);
    }
    .dashboard-hero:after{right:-140px;top:-185px;pointer-events:none}
    .dashboard-hero .hero-copy small{color:#b7c9fa;opacity:1;font-size:.68rem;letter-spacing:.1em}
    .dashboard-hero .hero-copy h2{font-size:1.45rem;letter-spacing:-.025em;font-weight:800}
    .dashboard-hero .hero-copy p{color:#d3def6;opacity:1;max-width:560px}
    .dashboard-overview-heading{margin-top:5px}
    .dashboard-heading{gap:14px;align-items:center}
    .dashboard-heading h4{font-weight:800;letter-spacing:-.018em;font-size:1.05rem;color:#1a2e4e}
    .dashboard-heading span,.chart-head small,.finance-head small{color:#5370b2;letter-spacing:.065em;font-size:.65rem}
    .dashboard-heading>small{background:#fff;border:1px solid var(--dash-border);border-radius:30px;padding:6px 10px;font-size:.69rem;white-space:normal}
    .metric-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .metric-card{
        position:relative;display:flex;flex-direction:column;align-items:stretch;justify-content:space-between;gap:17px;
        min-height:141px;padding:17px;border-radius:16px;overflow:hidden;
        border:1px solid #e7ecf3;background:#fff;box-shadow:0 3px 13px rgba(28,46,82,.035);
        transition:transform .16s ease,box-shadow .16s ease,border-color .16s ease;
    }
    .metric-card:before{content:"";position:absolute;inset-block:0;inset-inline-start:0;width:3px;background:var(--metric-tone,#4166cb);opacity:.75}
    .metric-card:after{content:"";position:absolute;width:108px;height:108px;border-radius:50%;inset-inline-end:-58px;bottom:-65px;background:var(--metric-soft,#eff3ff);opacity:.42;pointer-events:none}
    .metric-card:hover{transform:translateY(-2px);border-color:#cbd7ed;box-shadow:0 13px 27px rgba(35,54,91,.09)}
    .metric-blue{--metric-tone:#4166cb;--metric-soft:#eaf0ff}
    .metric-teal{--metric-tone:#11957a;--metric-soft:#e7f8f4}
    .metric-violet{--metric-tone:#7c5dd0;--metric-soft:#f0ebff}
    .metric-orange{--metric-tone:#e88932;--metric-soft:#fff2e6}
    .metric-green{--metric-tone:#27985b;--metric-soft:#e7f7ed}
    .metric-pink{--metric-tone:#cb568c;--metric-soft:#fcebf2}
    .metric-navy{--metric-tone:#3b5179;--metric-soft:#e9edf5}
    .metric-amber{--metric-tone:#bb8822;--metric-soft:#fff6da}
    .metric-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;position:relative;z-index:1}
    .metric-icon{flex:0 0 43px;width:43px;height:43px;border-radius:13px;background:var(--metric-soft);color:var(--metric-tone);font-size:1.15rem}
    .metric-teal .metric-icon,.metric-violet .metric-icon,.metric-orange .metric-icon,.metric-green .metric-icon,.metric-pink .metric-icon,.metric-navy .metric-icon,.metric-amber .metric-icon{background:var(--metric-soft);color:var(--metric-tone)}
    .metric-arrow{display:grid;place-items:center;width:26px;height:26px;border-radius:8px;margin:0;background:#f5f7fb;color:#8997ad;transition:color .15s,background .15s}
    .metric-card:hover .metric-arrow{background:var(--metric-soft);color:var(--metric-tone)}
    .metric-copy{position:relative;z-index:1;gap:3px}
    .metric-copy small{font-size:.75rem;font-weight:650;color:#68758b;line-height:1.35}
    .metric-copy strong{align-self:flex-start;font-size:1.75rem;font-weight:800;line-height:1.13;letter-spacing:-.04em;color:#1b2d48;margin:0;font-variant-numeric:tabular-nums}
    .panel{border:1px solid #e7ecf3;box-shadow:0 3px 13px rgba(28,46,82,.03);border-radius:16px;overflow:hidden}
    .balance-panel,.finance-card,.position-card,.chart-panel,.recent-panel{padding:18px}
    .panel-head{gap:11px;padding-bottom:15px}
    .panel-head-copy{flex:1;min-width:0}
    .panel-head h5,.finance-head h5,.chart-head h5{font-size:.92rem;font-weight:800;color:#243753;line-height:1.35}
    .panel-head p{font-size:.72rem;margin-top:4px;line-height:1.4}
    .panel-head-mark{color:#bdc7d7;display:grid;place-items:center}
    .panel-icon,.finance-icon,.position-title>span{flex-shrink:0;width:43px;height:43px;border-radius:13px;font-size:1.1rem}
    .currency-list{margin-top:3px}
    .currency-row{gap:12px;padding:13px 1px;border-bottom:1px solid #eef2f7;min-width:0}
    .currency-badge{background:#f0f4fa;border:1px solid #e5ebf4;border-radius:8px;color:#405576;font-size:.71rem;padding:6px 9px;flex-shrink:0}
    .currency-amount{min-width:0}
    .currency-amount strong{font-size:1rem;font-weight:800;letter-spacing:-.012em}
    .currency-amount small{margin-top:2px;font-size:.7rem}
    .position-grid{margin-top:12px}
    .position-title{margin-bottom:13px}
    .position-title h5{font-size:.95rem;font-weight:800;color:#243753;margin-top:2px}
    .position-title small{letter-spacing:.05em;font-size:.68rem}
    .position-row{gap:12px;align-items:center;padding:12px 0;border-top:1px solid #eef2f7}
    .position-row strong{font-size:.86rem;text-align:end}
    .finance-head{margin-bottom:16px;gap:11px}
    .finance-line{gap:12px;align-items:center;padding:13px 0;border-top:1px solid #eef2f7}
    .finance-line strong{font-size:.89rem;letter-spacing:-.015em;text-align:end}
    .closing-card{background:linear-gradient(155deg,#fff 0%,#f6f8ff 100%)}
    .closing-value{font-size:1.9rem;font-weight:800;letter-spacing:-.035em;text-align:start;overflow-wrap:anywhere;margin:17px 0}
    .closing-breakdown span{background:#fff;border:1px solid #e7ebf3;border-radius:8px;padding:7px 9px;font-size:.69rem;color:#5b6c85}
    .chart-head{align-items:flex-start;gap:12px;margin-bottom:16px}
    .chart-head h5{margin-top:4px}
    .chart-head>span{background:#f2f5fa;border-radius:25px;padding:5px 9px;font-size:.69rem}
    .chart-wrap{height:260px}
    .chart-head a{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border-radius:8px;background:#f1f5ff;color:#3157a5;font-size:.72rem;font-weight:750}
    .chart-head a:hover{background:#e5edff;text-decoration:none}
    .recent-panel{margin-top:12px}
    .activity-table{border-collapse:separate;border-spacing:0;min-width:650px}
    .activity-table th{background:#f7f9fd;padding:12px 13px;font-size:.67rem;font-weight:800;color:#64748b;letter-spacing:.055em;border-top:1px solid #eaf0f5;border-bottom:1px solid #eaf0f5}
    .activity-table th:first-child{border-start-start-radius:9px}
    .activity-table th:last-child{border-start-end-radius:9px}
    .activity-table td{padding:13px;font-size:.77rem;color:#3f506b;border-bottom:1px solid #edf1f6;vertical-align:middle}
    .activity-table tbody tr:hover td{background:#fafcff}
    .activity-table td strong{font-size:.79rem;font-weight:750;color:#263958}
    .activity-table td small{font-size:.69rem;margin-top:3px}
    .type-pill{padding:6px 9px;border-radius:7px;font-size:.7rem;font-weight:750;white-space:nowrap}
    .empty-state{background:#fafbfd;border:1px dashed #e9edf4;border-radius:10px;margin-top:10px;color:#8390a4}
    @media(max-width:1199px){.metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.chart-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:767px){
      .metric-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
      .metric-card{min-height:126px;padding:12px;gap:13px;border-radius:13px}
      .metric-icon{width:38px;height:38px;flex-basis:38px;font-size:1rem}
      .metric-copy strong{font-size:1.45rem}
      .metric-arrow{display:grid;width:23px;height:23px}
      .dashboard-heading{align-items:flex-start}
      .balance-panel,.finance-card,.position-card,.chart-panel,.recent-panel{padding:14px}
      .chart-grid{grid-template-columns:1fr}
      .chart-wrap{height:225px}
      .activity-table th,.activity-table td{padding:10px}
    }
    @media(max-width:400px){.metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.metric-card{min-height:124px}.metric-copy strong{font-size:1.27rem}.metric-copy small{font-size:.68rem}}
    @media(prefers-reduced-motion:reduce){.metric-card,.metric-arrow{transition:none}.metric-card:hover{transform:none}}
</style>
@endsection

@section('content')
<div class="dashboard-loader"><div class="loader-box"><span class="spinner-border spinner-border-sm"></span>{{ __('dashboard.updating') }}</div></div>
<div class="dashboard-page">
    <header class="dashboard-hero">
        <div class="dashboard-hero-top">
            <div class="hero-copy">
                <small>{{ __('dashboard.overview') }}</small>
                <h2>{{ __('dashboard.heading') }}</h2>
                <p>{{ __('dashboard.intro') }}</p>
            </div>
            @can('update settings')
                <button type="button" id="refreshSystemBalances" class="dashboard-refresh-btn" title="{{ __('ui.dashboard_refresh.button') }}">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    <span class="refresh-label">{{ __('ui.dashboard_refresh.button') }}</span>
                </button>
            @endcan
        </div>
        <form id="dashboardFilters" action="{{ route('admin.dashboard') }}" method="GET" class="filter-bar">
            <div class="dashboard-filter-grid">
                <div class="dashboard-filter-field"><label for="dashboardCurrency">{{ __('dashboard.currency') }}</label><select id="dashboardCurrency" class="form-select" name="currency_id"><option value="">{{ __('dashboard.all_currencies') }}</option>@foreach($currencies as $currency)<option value="{{ $currency->id }}" @selected($selectedCurrencyId === $currency->id)>{{ $currency->code }} — {{ $currency->name }}</option>@endforeach</select></div>
                <div class="dashboard-filter-field"><label for="periodPreset">{{ __('dashboard.period') }}</label><select class="form-select" name="preset" id="periodPreset"><option value="today" @selected($datePreset==='today')>{{ __('dashboard.today') }}</option><option value="yesterday" @selected($datePreset==='yesterday')>{{ __('dashboard.yesterday') }}</option><option value="this_week" @selected($datePreset==='this_week')>{{ __('dashboard.this_week') }}</option><option value="this_month" @selected($datePreset==='this_month')>{{ __('dashboard.this_month') }}</option><option value="last_30_days" @selected($datePreset==='last_30_days')>{{ __('dashboard.last_30_days') }}</option><option value="this_year" @selected($datePreset==='this_year')>{{ __('dashboard.this_year') }}</option><option value="custom" @selected($datePreset==='custom')>{{ __('dashboard.custom_range') }}</option></select></div>
                <div class="dashboard-filter-field custom-date"><label for="dashboardFrom">{{ __('dashboard.from') }}</label><input id="dashboardFrom" class="form-control" type="date" name="date_from" value="{{ $dateFrom }}"></div>
                <div class="dashboard-filter-field custom-date"><label for="dashboardTo">{{ __('dashboard.to') }}</label><input id="dashboardTo" class="form-control" type="date" name="date_to" value="{{ $dateTo }}"></div>
                <div class="dashboard-filter-field dashboard-filter-action"><button class="filter-submit" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>{{ __('dashboard.apply') }}</button></div>
            </div>
        </form>
    </header>
    @include('admin.dashboard-content')
</div>
@endsection

@section('js')
<script src="{{ asset('assets/offline/bd19dbbb831f8c42.js') }}"></script>
<script>
let dashboardCharts=[];
function toggleDates(){const custom=document.getElementById('periodPreset')?.value==='custom';document.querySelectorAll('.custom-date').forEach(el=>el.style.display=custom?'block':'none')}
function renderCharts(){dashboardCharts.forEach(chart=>chart.destroy());dashboardCharts=[];const node=document.getElementById('dashboardChartData');if(!node||typeof Chart==='undefined')return;let data;try{data=JSON.parse(node.textContent)}catch(e){return}const common={responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:9,usePointStyle:true,font:{size:10}}}},scales:{x:{grid:{display:false},ticks:{font:{size:9}}},y:{beginAtZero:true,grid:{color:'#eef2f6'},ticks:{font:{size:9}}}}};
 const flow=document.getElementById('dailyFlowChart');if(flow)dashboardCharts.push(new Chart(flow,{type:'line',data:{labels:data.dailyFlow.map(x=>x.date),datasets:[{label:@json(__('dashboard.credit')),data:data.dailyFlow.map(x=>Number(x.credit)),borderColor:'#149b78',backgroundColor:'rgba(20,155,120,.1)',fill:true,tension:.38},{label:@json(__('dashboard.debit')),data:data.dailyFlow.map(x=>Number(x.debit)),borderColor:'#e45466',backgroundColor:'rgba(228,84,102,.08)',fill:true,tension:.38}]},options:common}));
 const pnl=document.getElementById('profitLossChart');if(pnl)dashboardCharts.push(new Chart(pnl,{type:'bar',data:{labels:data.profitLoss.map(x=>x.currency),datasets:[{label:@json(__('dashboard.net_pnl')),data:data.profitLoss.map(x=>Number(x.net_profit)),backgroundColor:data.profitLoss.map(x=>Number(x.net_profit)>=0?'#3157d5':'#e45466'),borderRadius:7}]},options:common}));
 const labels=data.balanceLabels||[], balance=document.getElementById('balanceChart');if(balance)dashboardCharts.push(new Chart(balance,{type:'bar',data:{labels,datasets:[{label:@json(__('dashboard.cash')),data:labels.map(x=>Number(data.cashBalances[x]||0)),backgroundColor:'#3157d5',borderRadius:5},{label:@json(__('dashboard.customers')),data:labels.map(x=>Number(data.customerBalances[x]||0)),backgroundColor:'#7450d5',borderRadius:5},{label:@json(__('dashboard.sarafs')),data:labels.map(x=>Number(data.sarafBalances[x]||0)),backgroundColor:'#149b78',borderRadius:5}]},options:common}));}
document.addEventListener('DOMContentLoaded',()=>{toggleDates();renderCharts()});document.addEventListener('change',e=>{if(e.target.id==='periodPreset')toggleDates()});document.addEventListener('submit',async e=>{if(e.target.id!=='dashboardFilters')return;e.preventDefault();const form=e.target,from=form.querySelector('[name=date_from]'),to=form.querySelector('[name=date_to]');if(form.preset.value==='custom'&&(!from.value||!to.value||from.value>to.value)){Swal.fire({confirmButtonText:@json(__('dashboard.ok')),icon:'warning',title:@json(__('dashboard.invalid_dates')),text:@json(__('dashboard.select_dates'))});return}document.body.classList.add('dashboard-loading');const url=form.action+'?'+new URLSearchParams(new FormData(form));try{const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});if(!response.ok)throw new Error();const result=await response.json();const current=document.getElementById('dashboardContent');current.outerHTML=result.html;history.replaceState({},'',url);renderCharts()}catch(error){Swal.fire({confirmButtonText:@json(__('dashboard.ok')),icon:'error',title:@json(__('dashboard.update_failed')),text:@json(__('dashboard.try_again'))})}finally{document.body.classList.remove('dashboard-loading')}});
document.getElementById('refreshSystemBalances')?.addEventListener('click', async function () {
    const button = this;
    const label = button.querySelector('.refresh-label');
    button.disabled = true;
    button.classList.add('is-refreshing');
    button.setAttribute('aria-busy', 'true');
    if (label) label.textContent = @json(__('ui.dashboard_refresh.working'));
    try {
        const response = await fetch(@json(route('admin.dashboard.refresh-balances')), {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'}
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        await Swal.fire({confirmButtonText:@json(__('dashboard.ok')),icon: 'success', text: result.message, timer: 1500, showConfirmButton: false});
        window.location.reload();
    } catch (error) {
        Swal.fire({confirmButtonText:@json(__('dashboard.ok')),icon: 'error', text: error.message || @json(__('ui.dashboard_refresh.failed'))});
        button.disabled = false;
        button.classList.remove('is-refreshing');
        button.removeAttribute('aria-busy');
        if (label) label.textContent = @json(__('ui.dashboard_refresh.button'));
    }
});
</script>
@endsection
