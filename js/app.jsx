/* Ruta Nómada — App shell: top navbar + collapsible icon-rail sidebar + router */
const { useState: useStateApp, useEffect: useEffectApp } = React;

const TOP_NAV = [
  { id: "inicio", label: "Inicio", icon: "home" },
  { id: "explorar", label: "Explorar", icon: "explore" },
  { id: "misviajes", label: "Mis viajes", icon: "luggage" },
  { id: "comunidad", label: "Comunidad", icon: "groups" },
];
const SIDE_NAV = [
  { id: "perfil", label: "Perfil", icon: "account_circle" },
  { id: "misplanes", label: "Mis planes", icon: "map" },
  { id: "config", label: "Configuración", icon: "settings" },
];

function Topbar({ route, onNav }) {
  const topId = route.startsWith("top:") ? route.slice(4) : null;
  return (
    <header className="topbar">
      <div className="topbar__brand">
        <LogoMark size={34} />
        <span className="name">Ruta Nómada</span>
      </div>
      <nav className="topnav">
        {TOP_NAV.map((n) => (
          <button key={n.id} className={"topnav__item" + (topId === n.id ? " active" : "")} onClick={() => onNav("top:" + n.id)}>
            <Ico name={n.icon} fill={topId === n.id} />{n.label}
          </button>
        ))}
      </nav>
      <div className="topbar__spacer"></div>
      <div className="searchbox">
        <Ico name="search" />
        <input placeholder="Buscar destinos, actividades, hoteles…" />
      </div>
      <div className="topbar__user">
        <button className="icon-btn" aria-label="Notificaciones"><Ico name="notifications" /></button>
        <div className="topbar__greet">
          <div className="hi">¡Hola, Ana!</div>
          <div className="role">Viajera</div>
        </div>
        <button className="avatar" onClick={() => onNav("side:perfil")} aria-label="Perfil">A</button>
      </div>
    </header>
  );
}

function Sidebar({ collapsed, setCollapsed, route, onNav, onLogout }) {
  const sideId = route.startsWith("side:") ? route.slice(5) : null;
  return (
    <aside className={"sidebar" + (collapsed ? " collapsed" : "")}>
      <div className="sidebar__top">
        <button className="sidebar__toggle" onClick={() => setCollapsed(!collapsed)} aria-label="Mostrar/ocultar menú">
          <Ico name={collapsed ? "menu" : "menu_open"} />
        </button>
        {!collapsed && (
          <div className="sidebar__profile">
            <div className="avatar">A</div>
            <div className="sidebar__id">
              <div className="nm">Ana López</div>
              <div className="em">ana@rutanomada.mx</div>
            </div>
          </div>
        )}
      </div>

      <div className="sidebar__section-label">Mi cuenta</div>
      <nav className="sidebar__nav">
        {SIDE_NAV.map((n) => (
          <button key={n.id} className={"side-link" + (sideId === n.id ? " active" : "")} onClick={() => onNav("side:" + n.id)}>
            <Ico name={n.icon} fill={sideId === n.id} />
            <span className="side-link__label">{n.label}</span>
            <span className="side-link__tip">{n.label}</span>
          </button>
        ))}
      </nav>

      <div className="sidebar__foot">
        <button className="side-link side-link--danger" onClick={onLogout}>
          <Ico name="logout" />
          <span className="side-link__label">Cerrar sesión</span>
          <span className="side-link__tip">Cerrar sesión</span>
        </button>
      </div>
    </aside>
  );
}

function App() {
  // route forms: "top:inicio" | "side:perfil" | "detail"
  const [route, setRoute] = useStateApp(() => localStorage.getItem("rn_route") || "top:inicio");
  const [collapsed, setCollapsed] = useStateApp(() => localStorage.getItem("rn_sidebar") === "1");
  const [authed, setAuthed] = useStateApp(() => localStorage.getItem("rn_authed") === "1");
  const [dest, setDest] = useStateApp(null);

  useEffectApp(() => { localStorage.setItem("rn_route", route); }, [route]);
  useEffectApp(() => { localStorage.setItem("rn_sidebar", collapsed ? "1" : "0"); }, [collapsed]);
  useEffectApp(() => { localStorage.setItem("rn_authed", authed ? "1" : "0"); }, [authed]);

  // scroll main to top on route change
  useEffectApp(() => { const m = document.querySelector(".main"); if (m) m.scrollTop = 0; }, [route, dest]);

  if (!authed) return <AuthFlow onEnter={() => { setAuthed(true); setRoute("top:inicio"); }} />;

  const openDest = (d) => { setDest(d); setRoute("detail"); };
  const back = () => setRoute("top:inicio");

  let content;
  if (route === "detail" && dest) content = <DestinationDetail d={dest} onBack={back} />;
  else if (route === "top:inicio") content = <Inicio openDest={openDest} />;
  else if (route === "top:explorar") content = <Explorar openDest={openDest} />;
  else if (route === "top:misviajes") content = <MisViajes openDest={openDest} />;
  else if (route === "top:comunidad") content = <Comunidad />;
  else if (route === "side:perfil") content = <Perfil />;
  else if (route === "side:misplanes") content = <MisPlanes openDest={openDest} />;
  else if (route === "side:config") content = <Configuracion />;
  else content = <Inicio openDest={openDest} />;

  return (
    <div className="app">
      <Topbar route={route} onNav={setRoute} />
      <div className="app__body">
        <Sidebar collapsed={collapsed} setCollapsed={setCollapsed} route={route} onNav={setRoute}
          onLogout={() => { setAuthed(false); }} />
        <main className="main">
          <div className="main__inner" key={route + (dest ? dest.name : "")}>{content}</div>
        </main>
      </div>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
