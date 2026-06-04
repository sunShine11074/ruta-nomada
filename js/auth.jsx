/* Ruta Nómada — Auth flow: Login · Registro · Recuperación */
const { useState: useStateA } = React;

function BrandPanel() {
  return (
    <div className="auth__brand">
      <div className="auth__brand-top">
        <LogoMark size={40} />
        <span style={{ fontFamily: "'Noto Serif',serif", fontWeight: 700, fontSize: "1.4rem", color: "var(--barley-400)" }}>Ruta Nómada</span>
      </div>
      <div className="auth__pitch">
        <h1>Planifica viajes con alma de explorador y precisión de contador.</h1>
        <p>Descubre destinos, cotiza tu ruta y reparte gastos con tu grupo — todo en un mismo lugar.</p>
      </div>
      <div className="auth__brand-foot">
        <div className="auth__stat"><div className="n">120+</div><div className="l">Destinos</div></div>
        <div className="auth__stat"><div className="n">48k</div><div className="l">Viajeros</div></div>
        <div className="auth__stat"><div className="n">4.9★</div><div className="l">Valoración</div></div>
      </div>
    </div>
  );
}

function PwField({ label, placeholder, value, onChange }) {
  const [show, setShow] = useStateA(false);
  return (
    <div className="field">
      <label className="field__label">{label}</label>
      <div className="field__control">
        <Ico name="lock" />
        <input type={show ? "text" : "password"} placeholder={placeholder} value={value} onChange={(e) => onChange(e.target.value)} />
        <button type="button" className="field__toggle" onClick={() => setShow(!show)} aria-label="Mostrar contraseña">
          <Ico name={show ? "visibility_off" : "visibility"} />
        </button>
      </div>
    </div>
  );
}

function Login({ go, onEnter }) {
  const [email, setEmail] = useStateA("ana@rutanomada.mx");
  const [pw, setPw] = useStateA("");
  const [remember, setRemember] = useStateA(true);
  return (
    <div className="auth__card fade">
      <h2>Iniciar sesión</h2>
      <p className="auth__sub">Bienvenida de vuelta. Continúa planeando tu próxima aventura.</p>
      <form className="auth__form" onSubmit={(e) => { e.preventDefault(); onEnter(); }}>
        <div className="field">
          <label className="field__label">Email</label>
          <div className="field__control">
            <Ico name="mail" />
            <input type="email" placeholder="Ingresa tu email" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
        </div>
        <PwField label="Contraseña" placeholder="Ingresa tu contraseña" value={pw} onChange={setPw} />
        <div className="auth__row">
          <label className="check">
            <input type="checkbox" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
            <span className="check__box"><Ico name="check" /></span>
            Recordar mi contraseña
          </label>
          <button type="button" className="auth__link" onClick={() => go("recuperacion")}>¿Olvidaste tu contraseña?</button>
        </div>
        <Btn variant="cta" block type="submit" iconRight="arrow_forward">Ingresar</Btn>
      </form>
      <p className="auth__alt">¿No tienes cuenta? <button onClick={() => go("registro")}>Regístrate</button></p>
    </div>
  );
}

function Registro({ go, onEnter }) {
  const [f, setF] = useStateA({ nombre: "", email: "", pw: "", pw2: "", terms: false });
  const set = (k) => (v) => setF((s) => ({ ...s, [k]: v }));
  return (
    <div className="auth__card fade">
      <h2>Crear cuenta</h2>
      <p className="auth__sub">Crea una cuenta para comenzar a planear tus rutas.</p>
      <form className="auth__form" onSubmit={(e) => { e.preventDefault(); if (f.terms) onEnter(); }}>
        <div className="field">
          <label className="field__label">Nombre</label>
          <div className="field__control">
            <Ico name="person" />
            <input type="text" placeholder="Tu nombre completo" value={f.nombre} onChange={(e) => set("nombre")(e.target.value)} />
          </div>
        </div>
        <div className="field">
          <label className="field__label">Email</label>
          <div className="field__control">
            <Ico name="mail" />
            <input type="email" placeholder="tu@email.com" value={f.email} onChange={(e) => set("email")(e.target.value)} />
          </div>
        </div>
        <PwField label="Contraseña" placeholder="••••••••" value={f.pw} onChange={set("pw")} />
        <PwField label="Confirmar contraseña" placeholder="••••••••" value={f.pw2} onChange={set("pw2")} />
        <label className="check">
          <input type="checkbox" checked={f.terms} onChange={(e) => set("terms")(e.target.checked)} />
          <span className="check__box"><Ico name="check" /></span>
          He leído y acepto los <a href="#">Términos de Servicio</a>
        </label>
        <Btn variant="cta" block type="submit" iconRight="arrow_forward">Registrarse</Btn>
      </form>
      <p className="auth__alt">¿Ya tienes una cuenta? <button onClick={() => go("login")}>Inicia sesión</button></p>
    </div>
  );
}

function Recuperacion({ go }) {
  const [sent, setSent] = useStateA(false);
  const [email, setEmail] = useStateA("");
  if (sent) {
    return (
      <div className="auth__card fade">
        <div className="auth__success">
          <div className="auth__success-ring"><Ico name="mark_email_read" /></div>
          <h2 style={{ fontSize: "1.6rem" }}>Revisa tu correo</h2>
          <p className="auth__sub" style={{ marginBottom: 24 }}>
            Enviamos las instrucciones para restablecer tu contraseña a <b style={{ color: "var(--rino-200)" }}>{email || "tu correo"}</b>.
          </p>
          <Btn variant="ghost" block icon="arrow_back" onClick={() => go("login")}>Volver a iniciar sesión</Btn>
        </div>
      </div>
    );
  }
  return (
    <div className="auth__card fade">
      <button className="auth__back" onClick={() => go("login")}><Ico name="arrow_back" />Volver</button>
      <h2>Recuperación de contraseña</h2>
      <p className="auth__sub">Ingresa tu correo electrónico y te enviaremos las instrucciones para restablecer tu contraseña.</p>
      <form className="auth__form" onSubmit={(e) => { e.preventDefault(); setSent(true); }}>
        <div className="field">
          <label className="field__label">Correo electrónico</label>
          <div className="field__control">
            <Ico name="mail" />
            <input type="email" placeholder="ejemplo@correo.com" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
        </div>
        <Btn variant="cta" block type="submit" iconRight="send">Recuperar contraseña</Btn>
      </form>
      <p className="auth__alt">
        <button onClick={() => go("login")}>Inicio de sesión</button>
        <span style={{ margin: "0 8px", color: "var(--rino-600)" }}>|</span>
        <button onClick={() => go("registro")}>Regístrate</button>
      </p>
    </div>
  );
}

function AuthFlow({ onEnter }) {
  const [mode, setMode] = useStateA("login");
  return (
    <div className="auth">
      <BrandPanel />
      <div className="auth__panel">
        {mode === "login" && <Login go={setMode} onEnter={onEnter} />}
        {mode === "registro" && <Registro go={setMode} onEnter={onEnter} />}
        {mode === "recuperacion" && <Recuperacion go={setMode} />}
      </div>
    </div>
  );
}

window.AuthFlow = AuthFlow;
