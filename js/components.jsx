/* Ruta Nómada — shared UI components */
const { useState, useEffect, useRef } = React;

/* Brand logo mark — compass rose, organic + structured */
function LogoMark({ size = 34, color = "var(--cta)", ring = "var(--barley-400)" }) {
  return (
    <svg className="logo-mark" width={size} height={size} viewBox="0 0 40 40" fill="none" aria-hidden="true">
      <circle cx="20" cy="20" r="18.5" stroke={ring} strokeWidth="2.2" opacity="0.85" />
      <circle cx="20" cy="20" r="13" stroke={ring} strokeWidth="1" opacity="0.4" />
      <path d="M20 6 L24 20 L20 34 L16 20 Z" fill={color} />
      <path d="M6 20 L20 16 L34 20 L20 24 Z" fill={ring} opacity="0.9" />
      <circle cx="20" cy="20" r="2.4" fill="var(--rino-400)" />
    </svg>
  );
}

/* Elegant placeholder image — palette-tinted, iconographic, never an AI photo guess */
const PH_TINTS = {
  cultura:  "var(--neptune-600)",
  romance:  "var(--naples-600)",
  aventura: "var(--olive-600)",
  desierto: "var(--barley-500)",
  agua:     "var(--neptune-500)",
  bosque:   "var(--olive-500)",
  ciudad:   "var(--rino-700)",
};
const PH_ICONS = {
  cultura: "temple_buddhist", romance: "wine_bar", aventura: "landscape",
  desierto: "wb_sunny", agua: "sailing", bosque: "forest", ciudad: "apartment",
  hotel: "hotel", food: "restaurant", map: "map", video: "play_circle",
  beach: "beach_access", default: "image",
};
function Placeholder({ tint = "agua", icon, label }) {
  return (
    <div className="ph" style={{ "--ph-c": PH_TINTS[tint] || PH_TINTS.agua }}>
      <span className="ph__ico"><span className="material-symbols-outlined">{PH_ICONS[icon] || PH_ICONS[tint] || PH_ICONS.default}</span></span>
      {label && <span className="ph__tag"><span className="material-symbols-outlined">add_photo_alternate</span>{label}</span>}
    </div>
  );
}

/* Material icon shorthand */
function Ico({ name, fill, className = "", style }) {
  return <span className={"material-symbols-outlined" + (fill ? " fill" : "") + (className ? " " + className : "")} style={style}>{name}</span>;
}

/* Generic button */
function Btn({ variant = "cta", size, block, icon, iconRight, children, ...rest }) {
  const cls = ["btn", "btn--" + variant, size === "sm" && "btn--sm", block && "btn--block"].filter(Boolean).join(" ");
  return (
    <button className={cls} {...rest}>
      {icon && <Ico name={icon} />}
      {children}
      {iconRight && <Ico name={iconRight} />}
    </button>
  );
}

/* Destination card */
function DestinationCard({ d, onOpen }) {
  const [fav, setFav] = useState(d.fav || false);
  return (
    <article className="dcard" onClick={() => onOpen && onOpen(d)}>
      <div className="dcard__media">
        <span className="dcard__cat">{d.category}</span>
        <button className={"dcard__fav" + (fav ? " on" : "")} onClick={(e) => { e.stopPropagation(); setFav(!fav); }} aria-label="Guardar">
          <Ico name="favorite" />
        </button>
        <Placeholder tint={d.tint} icon={d.icon} label={d.name} />
      </div>
      <div className="dcard__body">
        <div className="dcard__title">{d.name}</div>
        <div className="dcard__meta"><Ico name="location_on" />{d.country}</div>
        <p className="dcard__desc">{d.desc}</p>
        <div className="dcard__foot">
          <div className="dcard__price">
            <span className="lbl">Desde</span>
            <span className="data" style={{ fontSize: "1.05rem", fontWeight: 600, color: "var(--rino-300)" }}>{d.price}</span>
          </div>
          <span className="dcard__rating"><Ico name="star" />{d.rating}</span>
        </div>
      </div>
    </article>
  );
}

/* Story / recently uploaded */
function StoryItem({ s }) {
  return (
    <article className="story">
      <div className="story__thumb"><Placeholder tint={s.tint} icon={s.icon} /></div>
      <div className="story__body">
        <span className="story__when">{s.when}</span>
        <div className="story__title">{s.title}</div>
        <span className="story__by">Por {s.by}</span>
      </div>
    </article>
  );
}

Object.assign(window, { LogoMark, Placeholder, Ico, Btn, DestinationCard, StoryItem });
