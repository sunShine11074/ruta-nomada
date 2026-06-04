/* Ruta Nómada — Destination detail (from f7 spec) */
const { useState: useStateD } = React;

function DestinationDetail({ d, onBack }) {
  const [tab, setTab] = useStateD("desc");
  const tabs = [
    { id: "desc", label: "Descripción" },
    { id: "hacer", label: "Qué hacer" },
    { id: "comer", label: "Dónde comer" },
    { id: "llegar", label: "Cómo llegar" },
    { id: "resenas", label: "Reseñas" },
  ];
  const highlights = [
    { t: "Zona Hotelera", s: "Playas y resorts", tint: "agua", icon: "beach" },
    { t: "Chichén Itzá", s: "Zona arqueológica", tint: "cultura", icon: "cultura" },
    { t: "Isla Mujeres", s: "Playas cristalinas", tint: "agua", icon: "sailing" },
    { t: "Xcaret", s: "Parque temático", tint: "bosque", icon: "forest" },
  ];
  const services = [
    ["flight", "Vuelos redondos"], ["hotel", "Hotel 5 estrellas"], ["restaurant", "Desayunos"],
    ["airport_shuttle", "Traslados"], ["confirmation_number", "Actividades"], ["support_agent", "Asistencia 24/7"],
  ];
  const reviews = [
    { q: "Un lugar increíble, playas hermosas y mucha diversión.", a: "María G.", w: "12 May 2023" },
    { q: "Excelente atención y hospedaje, ¡volveremos pronto!", a: "Luis P.", w: "28 Abr 2023" },
  ];
  return (
    <div className="fade">
      <div className="crumbs">
        <button onClick={onBack} style={{ color: "var(--rino-300)", fontWeight: 600 }}>Inicio</button>
        <Ico name="chevron_right" /><span>Destinos</span><Ico name="chevron_right" /><b>{d.name}</b>
      </div>

      {/* Hero */}
      <div style={{ borderRadius: "var(--radius)", overflow: "hidden", border: "1px solid var(--card-border)", height: 320, position: "relative", marginBottom: 24 }}>
        <Placeholder tint={d.tint} icon={d.icon} label={`Portada — ${d.name}`} />
        <button onClick={onBack} className="icon-btn" style={{ position: "absolute", top: 16, left: 16, background: "rgba(255,253,247,.9)", color: "var(--rino-300)" }}><Ico name="arrow_back" /></button>
      </div>

      <div className="grid" style={{ gridTemplateColumns: "1.7fr 1fr", alignItems: "start" }}>
        {/* Left column */}
        <div>
          <div style={{ display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap" }}>
            <h1 style={{ fontFamily: "'Noto Serif',serif", fontSize: "2.3rem", color: "var(--rino-400)" }}>{d.name}</h1>
            <span className="dcard__rating" style={{ fontSize: "1rem" }}><Ico name="star" />{d.rating}</span>
            <span style={{ color: "var(--ink-soft)", fontSize: ".9rem" }}>(256 opiniones)</span>
          </div>
          <p className="prose" style={{ marginTop: 12, color: "var(--ink)", maxWidth: "62ch" }}>
            {d.desc || "Disfruta de las playas paradisíacas, su cultura local, vida nocturna, actividades acuáticas y mucho más."}
          </p>

          <div style={{ display: "flex", gap: 10, flexWrap: "wrap", margin: "18px 0 4px" }}>
            <span className="badge-data"><Ico name="public" style={{ fontSize: 14 }} />{d.country || "México"}</span>
            <span className="badge-data"><Ico name="thermostat" style={{ fontSize: 14 }} />24°C – 32°C</span>
            <span className="badge-data" style={{ background: "var(--olive-600)" }}><Ico name="event_available" style={{ fontSize: 14 }} />Mejor: Dic – Abr</span>
          </div>

          {/* Tabs */}
          <div style={{ display: "flex", gap: 4, borderBottom: "1px solid var(--card-border)", margin: "26px 0 18px", flexWrap: "wrap" }}>
            {tabs.map((tb) => (
              <button key={tb.id} onClick={() => setTab(tb.id)}
                style={{ padding: "10px 14px", fontWeight: tab === tb.id ? 700 : 500, fontSize: ".94rem",
                  color: tab === tb.id ? "var(--rino-400)" : "var(--ink-soft)",
                  borderBottom: tab === tb.id ? "3px solid var(--cta)" : "3px solid transparent", marginBottom: -1 }}>
                {tb.label}
              </button>
            ))}
          </div>

          {tab === "desc" && (
            <div className="fade">
              <p className="prose" style={{ color: "var(--ink)" }}>
                {d.name} es uno de los destinos turísticos más famosos del mundo. Ofrece playas de arena blanca y mar
                turquesa, zonas arqueológicas cercanas, parques temáticos, cenotes, restaurantes de clase mundial y una
                vibrante vida nocturna. Ideal para viajes en pareja, familias y grupos de amigos.
              </p>
              <h3 style={{ fontFamily: "'Noto Serif',serif", color: "var(--rino-200)", fontSize: "1.3rem", margin: "26px 0 14px" }}>Lugares destacados</h3>
              <div className="grid grid--4" style={{ gap: 14 }}>
                {highlights.map((h) => (
                  <div key={h.t} style={{ borderRadius: 12, overflow: "hidden", border: "1px solid var(--card-border)", background: "var(--card)" }}>
                    <div style={{ height: 92 }}><Placeholder tint={h.tint} icon={h.icon} /></div>
                    <div style={{ padding: "10px 12px" }}>
                      <div style={{ fontWeight: 600, fontSize: ".92rem", color: "var(--rino-200)" }}>{h.t}</div>
                      <div style={{ fontSize: ".78rem", color: "var(--ink-soft)" }}>{h.s}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
          {tab === "resenas" && (
            <div className="fade" style={{ display: "flex", flexDirection: "column", gap: 14 }}>
              {reviews.map((r) => (
                <div className="panel" key={r.a} style={{ padding: 18 }}>
                  <p className="prose" style={{ fontStyle: "italic", color: "var(--rino-200)" }}>“{r.q}”</p>
                  <div style={{ marginTop: 8, fontSize: ".84rem", color: "var(--ink-soft)" }}><b style={{ color: "var(--ink-2)" }}>{r.a}</b> · <span className="data">{r.w}</span></div>
                </div>
              ))}
            </div>
          )}
          {["hacer", "comer", "llegar"].includes(tab) && (
            <div className="placeholder fade"><Ico name="travel_explore" /><h3>Contenido en preparación</h3><p>Esta sección se está completando para {d.name}.</p></div>
          )}
        </div>

        {/* Right column — booking + info */}
        <div style={{ display: "flex", flexDirection: "column", gap: 18, position: "sticky", top: 0 }}>
          <div className="panel panel--hi">
            <div style={{ display: "flex", alignItems: "flex-end", justifyContent: "space-between" }}>
              <div><span className="kv__k">Precio desde · por persona</span>
                <div className="data" style={{ fontSize: "1.9rem", fontWeight: 600, color: "var(--rino-400)" }}>{d.price}</div>
              </div>
            </div>
            <div style={{ display: "flex", flexDirection: "column", gap: 10, marginTop: 16 }}>
              <Btn variant="cta" block icon="request_quote">Cotizar este viaje</Btn>
              <Btn variant="secondary" block icon="bookmark">Guardar plan</Btn>
            </div>
          </div>
          <div className="panel">
            <h4 style={{ fontFamily: "'Inter',sans-serif", fontSize: "1.05rem", color: "var(--rino-100)", marginBottom: 12 }}>Servicios incluidos</h4>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
              {services.map(([ic, lb]) => (
                <div key={lb} style={{ display: "flex", alignItems: "center", gap: 8, fontSize: ".86rem", color: "var(--ink-2)" }}>
                  <Ico name={ic} style={{ color: "var(--neptune-200)", fontSize: 20 }} />{lb}
                </div>
              ))}
            </div>
          </div>
          <div className="panel" style={{ background: "var(--accent-soft)" }}>
            <h4 style={{ fontFamily: "'Inter',sans-serif", fontSize: "1.05rem", color: "var(--rino-100)", marginBottom: 10 }}>Información útil</h4>
            <div className="kv">
              {[["Moneda","Peso mexicano (MXN)"],["Idioma","Español"],["Voltaje","127 V"],["Emergencias","911"]].map(([k,v]) => (
                <div className="kv__row" key={k} style={{ padding: "10px 0", borderColor: "rgba(7,24,32,.08)" }}><span className="kv__k">{k}</span><span className="kv__v data" style={{ fontSize: ".88rem" }}>{v}</span></div>
              ))}
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  );
}

window.DestinationDetail = DestinationDetail;
