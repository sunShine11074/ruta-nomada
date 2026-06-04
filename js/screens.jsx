/* Ruta Nómada — content screens */
const { useState: useStateS } = React;

function Footer() {
  return (
    <footer className="footer">
      <span>© 2026 Ruta Nómada. Todos los derechos reservados.</span>
      <div className="footer__links">
        <a href="#">Sobre nosotros</a><a href="#">Ayuda</a><a href="#">Términos y condiciones</a><a href="#">Contacto</a>
      </div>
    </footer>
  );
}

/* ─────────────────────────── INICIO ─────────────────────────── */
function Inicio({ openDest }) {
  return (
    <div className="fade">
      <div className="page-head">
        <h1>Hola Ana, ¿a dónde vamos?</h1>
        <p>Explora destinos seleccionados para ti y retoma tus rutas guardadas.</p>
      </div>

      <div className="panel panel--hi" style={{ display: "flex", alignItems: "center", gap: 14, padding: 14 }}>
        <Ico name="search" style={{ color: "var(--ink-soft)" }} />
        <input placeholder="Buscar destinos, actividades, hoteles…" style={{ flex: 1, background: "transparent", border: "none", fontSize: "1rem", color: "var(--ink)" }} />
        <Btn variant="cta" size="sm" icon="tune">Filtrar</Btn>
      </div>

      <div className="sec-head"><h2>Recomendado para ti</h2><a className="more" href="#">Ver todos <Ico name="chevron_right" /></a></div>
      <div className="chips" style={{ marginBottom: 20 }}>
        {CATEGORIES.slice(1, 5).map((c) => (
          <span key={c.id} className="chip"><Ico name={c.icon} />{c.label}</span>
        ))}
      </div>
      <div className="grid grid--4">
        {DESTINATIONS.slice(0, 4).map((d) => <DestinationCard key={d.name} d={d} onOpen={openDest} />)}
      </div>

      <div className="sec-head"><h2>Subido recientemente</h2><a className="more" href="#">Ver más <Ico name="chevron_right" /></a></div>
      <div className="grid grid--2">
        {STORIES.map((s) => <StoryItem key={s.title} s={s} />)}
      </div>

      <Footer />
    </div>
  );
}

/* ─────────────────────────── EXPLORAR ─────────────────────────── */
function Explorar({ openDest }) {
  const [cat, setCat] = useStateS("todos");
  const list = cat === "todos" ? DESTINATIONS : DESTINATIONS.filter((d) => d.category === cat);
  return (
    <div className="fade">
      <div className="page-head">
        <h1>Explorar destinos</h1>
        <p>120+ destinos alrededor del mundo, listos para cotizar y planear.</p>
      </div>
      <div className="chips" style={{ marginBottom: 24 }}>
        {CATEGORIES.map((c) => (
          <button key={c.id} className={"chip" + (cat === c.id ? " active" : "")} onClick={() => setCat(c.id)}>
            <Ico name={c.icon} />{c.label}
          </button>
        ))}
      </div>
      {list.length ? (
        <div className="grid grid--3">
          {list.map((d) => <DestinationCard key={d.name} d={d} onOpen={openDest} />)}
        </div>
      ) : (
        <div className="placeholder"><Ico name="travel_explore" /><h3>Sin resultados</h3><p>No hay destinos en esta categoría todavía.</p></div>
      )}
      <Footer />
    </div>
  );
}

/* ─────────────────────────── MIS VIAJES ─────────────────────────── */
const STATUS_TINT = { Confirmado: "var(--olive-400)", Planeando: "var(--naples-400)", Borrador: "var(--rino-600)" };
function Trip({ t, openDest }) {
  return (
    <article className="dcard" onClick={() => openDest({ name: t.name.split(",")[0], country: t.name.split(",")[1] || "", category: t.status, tint: t.tint, icon: t.icon, desc: "", price: t.budget + " MXN", rating: "4.8" })}>
      <div className="dcard__media" style={{ aspectRatio: "16/7" }}>
        <span className="dcard__cat" style={{ background: STATUS_TINT[t.status], color: "var(--rino-100)" }}>{t.status}</span>
        <Placeholder tint={t.tint} icon={t.icon} label={t.name} />
      </div>
      <div className="dcard__body">
        <div className="dcard__title">{t.name}</div>
        <div className="dcard__meta"><Ico name="calendar_month" /><span className="data">{t.dates}</span></div>
        <div className="dcard__foot" style={{ marginTop: 10 }}>
          <span className="badge-data"><Ico name="group" style={{ fontSize: 14 }} />{t.people} viajeros</span>
          <span className="dcard__price"><span className="lbl">Presupuesto</span><span className="data" style={{ fontWeight: 600, color: "var(--rino-300)" }}>{t.budget}</span></span>
        </div>
      </div>
    </article>
  );
}
function MisViajes({ openDest }) {
  return (
    <div className="fade">
      <div className="page-head" style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end" }}>
        <div><h1>Mis viajes</h1><p>Tus rutas confirmadas y en planeación.</p></div>
        <Btn variant="cta" icon="add">Nuevo viaje</Btn>
      </div>
      <div className="grid grid--3" style={{ marginBottom: 8 }}>
        {[["3","Viajes activos"],["1","Confirmado"],["$33,300","Presupuesto total"]].map(([n,l]) => (
          <div className="stat-card" key={l}><div className="n">{n}</div><div className="l">{l}</div></div>
        ))}
      </div>
      <div className="sec-head"><h2>Todos los viajes</h2></div>
      <div className="grid grid--3">
        {MY_TRIPS.map((t) => <Trip key={t.name} t={t} openDest={openDest} />)}
      </div>
      <Footer />
    </div>
  );
}

/* ─────────────────────────── COMUNIDAD ─────────────────────────── */
function Comunidad() {
  const posts = [
    { ...STORIES[0], likes: 248, comments: 32 },
    { ...STORIES[1], likes: 187, comments: 19 },
    { ...STORIES[2], likes: 421, comments: 56 },
    { ...STORIES[3], likes: 96, comments: 11 },
  ];
  return (
    <div className="fade">
      <div className="page-head" style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end" }}>
        <div><h1>Comunidad</h1><p>Rutas, diarios y recomendaciones de otros viajeros.</p></div>
        <Btn variant="secondary" icon="edit">Compartir ruta</Btn>
      </div>
      <div className="grid grid--2">
        {posts.map((p) => (
          <article className="dcard" key={p.title}>
            <div className="dcard__media" style={{ aspectRatio: "16/8" }}><Placeholder tint={p.tint} icon={p.icon} label={p.title} /></div>
            <div className="dcard__body">
              <span className="story__when">{p.when} · {p.by}</span>
              <div className="dcard__title" style={{ fontSize: "1.15rem" }}>{p.title}</div>
              <div className="dcard__foot" style={{ marginTop: 8 }}>
                <span className="dcard__meta"><Ico name="favorite" style={{ fontSize: 17 }} /> {p.likes}</span>
                <span className="dcard__meta"><Ico name="chat_bubble" style={{ fontSize: 16 }} /> {p.comments}</span>
                <a className="more" href="#" style={{ color: "var(--rino-300)", fontWeight: 600 }}>Leer <Ico name="chevron_right" /></a>
              </div>
            </div>
          </article>
        ))}
      </div>
      <Footer />
    </div>
  );
}

/* ─────────────────────────── PERFIL ─────────────────────────── */
function Perfil() {
  return (
    <div className="fade">
      <div className="crumbs"><span>Cuenta</span><Ico name="chevron_right" /><b>Perfil</b></div>
      <div className="panel panel--hi" style={{ marginBottom: 24 }}>
        <div className="profile-hero">
          <div className="avatar-lg">A</div>
          <div style={{ flex: 1 }}>
            <h2 style={{ fontFamily: "'Noto Serif',serif", color: "var(--rino-300)", fontSize: "1.7rem" }}>Ana López</h2>
            <p style={{ color: "var(--ink-soft)" }}>Viajera · Miembro desde 2024</p>
            <div style={{ display: "flex", gap: 8, marginTop: 10 }}>
              <span className="badge-data"><Ico name="verified" style={{ fontSize: 14 }} />Cuenta verificada</span>
              <span className="badge-data" style={{ background: "var(--olive-600)" }}><Ico name="workspace_premium" style={{ fontSize: 14 }} />Plan Explorador</span>
            </div>
          </div>
          <Btn variant="ghost" icon="edit">Editar</Btn>
        </div>
      </div>
      <div className="grid grid--2">
        <div className="panel">
          <h3 style={{ fontFamily: "'Noto Serif',serif", color: "var(--rino-200)", fontSize: "1.3rem", marginBottom: 4 }}>Datos personales</h3>
          <div className="kv">
            {[["Nombre","Ana López"],["Email","ana@rutanomada.mx"],["Teléfono","+52 55 1234 5678"],["País","México"],["Idioma","Español"]].map(([k,v]) => (
              <div className="kv__row" key={k}><span className="kv__k">{k}</span><span className="kv__v">{v}</span></div>
            ))}
          </div>
        </div>
        <div className="panel">
          <h3 style={{ fontFamily: "'Noto Serif',serif", color: "var(--rino-200)", fontSize: "1.3rem", marginBottom: 4 }}>Actividad</h3>
          <div className="kv">
            {[["Viajes realizados","7"],["Rutas guardadas","12"],["Reseñas escritas","18"],["Gastos repartidos","$42,300 MXN"]].map(([k,v]) => (
              <div className="kv__row" key={k}><span className="kv__k">{k}</span><span className="kv__v data">{v}</span></div>
            ))}
          </div>
        </div>
      </div>
      <Footer />
    </div>
  );
}

/* ─────────────────────────── MIS PLANES ─────────────────────────── */
function MisPlanes({ openDest }) {
  return (
    <div className="fade">
      <div className="crumbs"><span>Cuenta</span><Ico name="chevron_right" /><b>Mis planes</b></div>
      <div className="page-head"><h1>Mis planes</h1><p>Cotizaciones guardadas y borradores de ruta.</p></div>
      <div className="grid grid--3">
        {MY_TRIPS.map((t) => <Trip key={t.name} t={t} openDest={openDest} />)}
      </div>
      <Footer />
    </div>
  );
}

/* ─────────────────────────── CONFIGURACIÓN ─────────────────────────── */
function Toggle({ on, onClick }) { return <button className={"toggle" + (on ? " on" : "")} onClick={onClick} aria-pressed={on}></button>; }
function Configuracion() {
  const [s, setS] = useStateS({ correo: true, push: false, ofertas: true, oscuro: false, ubic: true });
  const t = (k) => () => setS((p) => ({ ...p, [k]: !p[k] }));
  const rows = [
    ["Notificaciones por correo", "Resúmenes de viaje y recordatorios", "correo"],
    ["Notificaciones push", "Alertas en tiempo real en tu dispositivo", "push"],
    ["Ofertas y promociones", "Descuentos en destinos que sigues", "ofertas"],
    ["Compartir ubicación", "Mejora las recomendaciones cercanas", "ubic"],
  ];
  return (
    <div className="fade">
      <div className="crumbs"><span>Cuenta</span><Ico name="chevron_right" /><b>Configuración</b></div>
      <div className="page-head"><h1>Configuración</h1><p>Administra notificaciones, privacidad y preferencias.</p></div>
      <div className="grid grid--2">
        <div className="panel">
          <h3 style={{ fontFamily: "'Noto Serif',serif", color: "var(--rino-200)", fontSize: "1.3rem", marginBottom: 4 }}>Notificaciones</h3>
          <div className="kv">
            {rows.map(([k, sub, key]) => (
              <div className="kv__row" key={key}>
                <span><span className="kv__v" style={{ display: "block" }}>{k}</span><span className="kv__k">{sub}</span></span>
                <Toggle on={s[key]} onClick={t(key)} />
              </div>
            ))}
          </div>
        </div>
        <div className="panel">
          <h3 style={{ fontFamily: "'Noto Serif',serif", color: "var(--rino-200)", fontSize: "1.3rem", marginBottom: 4 }}>Preferencias</h3>
          <div className="kv">
            <div className="kv__row"><span className="kv__k">Moneda</span><span className="kv__v data">MXN · Peso mexicano</span></div>
            <div className="kv__row"><span className="kv__k">Idioma</span><span className="kv__v">Español</span></div>
            <div className="kv__row"><span className="kv__k">Zona horaria</span><span className="kv__v data">GMT-6</span></div>
            <div className="kv__row"><span><span className="kv__v" style={{ display: "block" }}>Tema oscuro</span><span className="kv__k">Reduce el brillo de la interfaz</span></span><Toggle on={s.oscuro} onClick={t("oscuro")} /></div>
          </div>
          <div style={{ marginTop: 18, display: "flex", gap: 10 }}>
            <Btn variant="cta">Guardar cambios</Btn>
            <Btn variant="ghost">Cancelar</Btn>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  );
}

Object.assign(window, { Inicio, Explorar, MisViajes, Comunidad, Perfil, MisPlanes, Configuracion, Footer });
