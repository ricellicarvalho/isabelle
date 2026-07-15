<style>
    .portal-solutions { --ink:#172033; --muted:#667085; display:flex; flex-direction:column; gap:1.15rem; scroll-behavior:smooth; }
    .portal-solutions__hero { position:relative; isolation:isolate; display:grid; grid-template-columns:minmax(0,1.65fr) minmax(220px,.6fr); gap:2rem; overflow:hidden; padding:clamp(1.6rem,4vw,3.15rem); border:1px solid rgba(245,158,11,.3); border-radius:1.25rem; background:linear-gradient(130deg,#fffdf7 0%,#fff7df 52%,#f8edff 100%); box-shadow:0 18px 50px rgba(120,86,24,.1); }
    .portal-solutions__glow { position:absolute; z-index:-1; border-radius:999px; filter:blur(4px); opacity:.75; }
    .portal-solutions__glow--one { width:260px; height:260px; right:-70px; top:-100px; background:rgba(245,158,11,.24); }
    .portal-solutions__glow--two { width:190px; height:190px; right:18%; bottom:-120px; background:rgba(124,58,237,.16); }
    .portal-solutions__hero-content { max-width:780px; }
    .portal-solutions__overline { display:inline-flex; align-items:center; gap:.45rem; margin-bottom:1rem; color:#a45f08; font-size:.72rem; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .portal-solutions__overline svg { width:1rem; height:1rem; }
    .portal-solutions__hero h2 { max-width:720px; margin:0; color:var(--ink); font-size:clamp(1.65rem,3vw,2.65rem); font-weight:900; letter-spacing:-.04em; line-height:1.08; }
    .portal-solutions__hero p { max-width:720px; margin:1rem 0 0; color:#525d70; font-size:clamp(.9rem,1.5vw,1.02rem); line-height:1.75; }
    .portal-solutions__actions { display:flex; flex-wrap:wrap; align-items:center; gap:.75rem; margin-top:1.45rem; }
    .portal-solutions__actions a, .portal-solutions__closing a { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; min-height:44px; border-radius:.72rem; padding:.7rem 1rem; font-size:.82rem; font-weight:800; text-decoration:none; transition:transform .18s ease,box-shadow .18s ease,background .18s ease; }
    .portal-solutions__actions a:hover, .portal-solutions__closing a:hover { transform:translateY(-2px); }
    .portal-solutions__primary-action { background:linear-gradient(135deg,#f59e0b,#d97706); color:white; box-shadow:0 8px 20px rgba(217,119,6,.25); }
    .portal-solutions__primary-action svg, .portal-solutions__closing a svg { width:1rem; height:1rem; }
    .portal-solutions__secondary-action { border:1px solid rgba(180,112,12,.25); background:rgba(255,255,255,.7); color:#8d5205; }
    .portal-solutions__hero-mark { align-self:center; justify-self:end; display:flex; width:180px; height:180px; flex-direction:column; align-items:center; justify-content:center; border:1px solid rgba(255,255,255,.8); border-radius:50%; background:rgba(255,255,255,.54); box-shadow:inset 0 0 0 12px rgba(255,255,255,.24),0 18px 40px rgba(127,83,13,.12); backdrop-filter:blur(10px); text-align:center; }
    .portal-solutions__hero-mark img { display:block; width:76px; height:76px; margin-bottom:.3rem; object-fit:contain; }
    .portal-solutions__hero-mark small { max-width:120px; color:#6f5c42; font-size:.65rem; font-weight:700; line-height:1.4; }
    .portal-solutions__grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; scroll-margin-top:6rem; }
    .portal-solutions__card { --accent:#d97706; --soft:#fff8e8; position:relative; display:flex; min-width:0; flex-direction:column; padding:1.35rem; overflow:hidden; border:1px solid #e8eaf0; border-radius:1rem; background:#fff; box-shadow:0 5px 22px rgba(28,39,60,.06); transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
    .portal-solutions__card:hover { transform:translateY(-3px); border-color:color-mix(in srgb,var(--accent) 35%,#e8eaf0); box-shadow:0 14px 30px rgba(28,39,60,.1); }
    .portal-solutions__card--violet { --accent:#7c3aed; --soft:#f5f0ff; }
    .portal-solutions__card--sky { --accent:#0284c7; --soft:#edf8ff; }
    .portal-solutions__card-header { display:flex; align-items:center; gap:.85rem; }
    .portal-solutions__icon { display:grid; width:44px; height:44px; flex:0 0 44px; place-items:center; border-radius:.8rem; background:var(--soft); color:var(--accent); }
    .portal-solutions__icon svg { width:1.45rem; height:1.45rem; }
    .portal-solutions__card-header span { display:block; margin-bottom:.14rem; color:var(--accent); font-size:.65rem; font-weight:850; letter-spacing:.06em; text-transform:uppercase; }
    .portal-solutions__card h3 { margin:0; color:var(--ink); font-size:1.08rem; font-weight:850; }
    .portal-solutions__description { min-height:3.8rem; margin:1rem 0; color:var(--muted); font-size:.78rem; line-height:1.6; }
    .portal-solutions__card ul { display:flex; flex:1; flex-direction:column; gap:.68rem; margin:0; padding:1rem 0; border-top:1px solid #eef0f4; list-style:none; }
    .portal-solutions__card li { display:flex; align-items:flex-start; gap:.55rem; color:#354052; font-size:.76rem; font-weight:650; line-height:1.4; }
    .portal-solutions__card li > span { display:grid; width:18px; height:18px; flex:0 0 18px; place-items:center; border-radius:50%; background:var(--soft); color:var(--accent); }
    .portal-solutions__card li svg { width:.7rem; height:.7rem; stroke-width:3; }
    .portal-solutions__card-action { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-top:.65rem; color:var(--accent); font-size:.74rem; font-weight:850; text-decoration:none; }
    .portal-solutions__card-action svg { width:.9rem; height:.9rem; transition:transform .18s ease; }
    .portal-solutions__card-action:hover svg { transform:translateX(3px); }
    .portal-solutions__closing { display:flex; align-items:center; justify-content:space-between; gap:1.5rem; padding:1.3rem 1.5rem; border:1px solid #efe2c5; border-radius:1rem; background:linear-gradient(110deg,#211c17,#3d2c1b); color:white; }
    .portal-solutions__closing span { color:#fbbf24; font-size:.66rem; font-weight:850; letter-spacing:.07em; text-transform:uppercase; }
    .portal-solutions__closing h3 { margin:.2rem 0 0; font-size:1rem; font-weight:850; }
    .portal-solutions__closing p { margin:.3rem 0 0; color:#d7d0c8; font-size:.75rem; }
    .portal-solutions__closing a { flex:0 0 auto; background:white; color:#7c4705; }
    .portal-solutions--compact .portal-solutions__hero { padding:clamp(1.4rem,3vw,2.35rem); }
    .portal-solutions--compact .portal-solutions__hero h2 { font-size:clamp(1.45rem,2.5vw,2.15rem); }
    @media (max-width:900px) { .portal-solutions__grid { grid-template-columns:1fr; } .portal-solutions__description { min-height:0; } }
    @media (max-width:700px) { .portal-solutions__hero { grid-template-columns:1fr; } .portal-solutions__hero-mark { display:none; } .portal-solutions__closing { align-items:flex-start; flex-direction:column; } .portal-solutions__closing a { width:100%; } }
    @media (max-width:520px) { .portal-solutions__hero { padding:1.3rem; border-radius:1rem; } .portal-solutions__actions { align-items:stretch; flex-direction:column; } .portal-solutions__actions a { width:100%; } .portal-solutions__card { padding:1.1rem; } }
    .dark .portal-solutions__hero { border-color:rgba(245,158,11,.25); background:linear-gradient(130deg,#211d18,#292219 55%,#251e2e); }
    .dark .portal-solutions__hero h2,.dark .portal-solutions__card h3 { color:#f8fafc; }
    .dark .portal-solutions__hero p,.dark .portal-solutions__description { color:#cbd5e1; }
    .dark .portal-solutions__secondary-action { border-color:#574630; background:rgba(31,25,19,.65); color:#fbbf24; }
    .dark .portal-solutions__hero-mark { border-color:#4a3b2b; background:rgba(36,29,22,.72); }
    .dark .portal-solutions__card { border-color:#374151; background:#1f2937; }
    .dark .portal-solutions__card ul { border-color:#374151; }
    .dark .portal-solutions__card li { color:#dbe2ea; }
</style>
