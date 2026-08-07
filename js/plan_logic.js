class Component extends DCLogic {
  constructor(props) {
    super(props);
    this.PIN = { atr: '#7B61FF', com: '#E8365D', sav: '#12808C', act: '#F0B429', hot: '#0C8BD7' };
    this.PLACES = [
      { id: 'playa', num: 1, cat: 'atr', sec: 'top', name: 'Playa Hermosa', rating: 4.2, rev: 3210, chips: ['Playa', 'Familiar'], more: 2, seed: 'rn-en-playa', x: 30, y: 74,
        desc: 'Playa Hermosa es una playa de arena con olas que rompen en ambos lados, variando según la temporada. Aunque no es conocida por sus olas de alta calidad, la zona ofrece servicios esenciales como baños, duchas, basureros, salvavidas, instalaciones de estacionamiento, palapas para sombra y una tienda cercana para mayor comodidad.',
        rvw: { t: 'Muy tranquila entre semana; el estacionamiento es amplio y la arena se mantiene limpia. Ideal para ir con niños por la tarde.', stars: 4, who: 'Carolina M' } },
      { id: 'mercado', num: 2, cat: 'atr', sec: 'top', name: 'Mercado Negro', rating: 4.4, rev: 12502, chips: ['Lonja de pescado', 'Compras'], more: 2, seed: 'rn-en-mercado', x: 46.5, y: 27, added: true,
        desc: 'El Mercado Negro es la lonja de pescados y mariscos del puerto de Ensenada, activa desde 1958. Entre sus puestos se consigue el producto del día —atún, jurel, almeja, erizo— y en los locales de los alrededores preparan los famosos tacos de pescado estilo Ensenada. Es una parada obligada para entender la cocina del mar de Baja California.',
        rvw: { t: 'El mejor lugar para comprar mariscos frescos y comerte unos tacos de pescado recién hechos. Llega temprano para ver descargar el producto.', stars: 5, who: 'Rubén T' } },
      { id: 'bufadora', num: 3, cat: 'atr', sec: 'top', name: 'La Bufadora', rating: 4.6, rev: 28904, chips: ['Géiser marino', 'Mirador'], more: 1, seed: 'rn-en-bufadora', x: 18, y: 90,
        desc: 'La Bufadora es uno de los géiseres marinos más grandes del mundo: el oleaje comprime el aire en una cueva y lanza chorros de agua de más de 20 metros. El andador que lleva al mirador está lleno de puestos de mariscos, churros y artesanía. Está a unos 40 minutos del centro, en la punta de la península de Punta Banda.',
        rvw: { t: 'Impresionante cuando el mar está picado. El camino de puestos es parte de la experiencia; prueba las almejas preparadas.', stars: 5, who: 'Fernanda G' } },
      { id: 'sabina', num: 1, cat: 'com', sec: 'eat', name: 'Sabina Restaurante La Guerrerense Carreta', rating: 4.6, rev: 5874, chips: ['Restaurante'], price: 2, more: 2, seed: 'rn-en-sabina', x: 49, y: 34,
        desc: 'La Guerrerense Carreta es un renombrado bar de tacos y ceviche ubicado en Ensenada, México. El restaurante es conocido por sus deliciosos platillos de mariscos y es propiedad de Sabina Bandera y Eduardo Oviedo. Además, recientemente han abierto Restaurante Sabina cerca, ofreciendo las mismas opciones de mariscos deliciosos. Otro lugar recomendado para los entusiastas de los mariscos es Muelle 3, un pequeño restaurante cerca del Malecón iniciado por los chefs famosos Benito Molina y Solange Muris.',
        rvw: { t: 'Decidí ir al restaurante y la primera persona que nos atendió muy cortante y malacarienta. Pero el demás personal se portó muy amable. La comida estaba bien, pero tenía mejores expectativas. Está mejor en la carreta de afuera.', stars: 4, who: 'Alejandro L' } },
      { id: 'manzanilla', num: 2, cat: 'com', sec: 'eat', name: 'Manzanilla', rating: 4.6, rev: 5874, chips: ['Restaurante'], price: 2, more: 2, seed: 'rn-en-manza', x: 44, y: 33, recent: true,
        desc: 'Manzanilla es un restaurante de visita obligada en el municipio de Ensenada, México. Es conocido por su marisco mexicano gourmet, lo que lo convierte en un favorito entre los locales y los visitantes por igual. El restaurante es parte de la vibrante escena culinaria de Baja California, que incluye otros establecimientos gastronómicos notables como Mision 19, Oryx, Tacos El Franc en Tijuana, y muchos más. Además, la región cuenta con una variedad de cervecerías artesanales como Cervecería Wendlandt.',
        rvw: { t: 'Este restaurante es excelente y cuenta con una atención excepcional hacia los comensales. Los platillos son deliciosos; probamos un tiradito de pescado que fue totalmente diferente a lo que había probado antes. El sabor del pescado era destacado y el jugo con aceite aromático con el que estaba bañado tenía', stars: 4, who: 'Azy A' } }
    ];
    this.POIS = [
      { n: 'Taqueria La México', t: 'com', x: 22, y: 22 }, { n: 'Tacos Fénix', t: 'com', x: 20, y: 47 },
      { n: 'Datoni Ensenada', t: 'com', x: 36, y: 30 }, { n: 'Centro Comercial Misión', t: 'shop', x: 42, y: 36 },
      { n: 'Los Globos Tianguis', t: 'shop', x: 56, y: 43 }, { n: 'CAPITAL O Hotel Rose, Ensenada', t: 'hotel', x: 45, y: 52 },
      { n: 'San Nicolas Hotel & Casino', t: 'hotel', x: 41, y: 57 }, { n: 'Plaza Sendero Ensenada', t: 'shop', x: 82, y: 72 },
      { n: "Sam's Club Ensenada", t: 'shop', x: 55, y: 82 }, { n: 'Malecón Ensenada', t: 'park', x: 34, y: 86 },
      { n: 'Símbolos Históricos de Ensenada', t: 'atr', x: 52, y: 22 }, { n: 'Aduana de Ensenada', t: 'atr', x: 48, y: 41 },
      { n: 'Ensenada Mirador', t: 'atr', x: 12, y: 8 }, { n: 'Bandera Monumental', t: 'atr', x: 78, y: 30 },
      { n: 'Unidad Deportiva Francisco Villa', t: 'park', x: 88, y: 22 }
    ];
    this.CATALL = [
      { t: 'Comida', id: 'catSecComida', items: [['restaurantes', 'Restaurantes'], ['cafes', 'Cafés'], ['comida-rapida', 'Comida rápida'], ['desayuno', 'Desayuno y brunch'], ['familiares', 'Restaurantes familiares'], ['tacos', 'Tacos'], ['italianos', 'Restaurantes italianos'], ['asiatica', 'Comida asiática'], ['pollo', 'Pollo'], ['mariscos', 'Restaurantes de mariscos']] },
      { t: 'Bebidas', id: 'catSecBebidas', items: [['barras', 'Barras'], ['fiesta', 'Lugares para salir de fiesta']] },
      { t: 'Atracciones', id: 'catSecAtracciones', items: [['atracciones', 'Atracciones'], ['ninos', 'Atracciones aptas para niños'], ['playas', 'Playas']] }
    ];
    this.CHIPS_SUG = ['Mejores lugares para comer en Ensenada', 'Itinerarios de 3 días a Ensenada', 'Principales atracciones en Ensenada'];
    this.state = {
      view: 'resumen', side: 'exp', prevSide: 'exp', scrollSec: 'resumen', sideFading: false, sideWpx: 192, exOpen: false, exIn: false, exAtRest: false, chatIn: false, chatAtRest: false, catAllOpen: false, catQ: '',
      grpRes: true, grpIt: true, grpPres: true,
      userMenu: false, catOpen: false, catLabel: 'Ciudad',
      added: { mercado: true }, addMenu: null, justAdded: null,
      hoverPlace: null, detail: null, detailFrom: null, detailTab: 'about', detailLoading: false,
      layersOpen: false, mapSearchOpen: false, mapSearchQ: '',
      // routeLines arranca encendido: si hay que descubrir un enlace escondido
      // en el menú de capas para ver las rutas, para el usuario no existen.
      // rutaSolo aísla la ruta de UN día sin ocultar los pines de los demás
      // (layerChecks sí oculta pines, ver _pinList).
      layerChecks: {}, routeLines: true, rutaSolo: null, modoMenu: null,
      chatMode: null, chatLog: [], chatInput: '', streaming: false, streamTxt: '',
      dayOpen: { 0: true, 1: true }, daySubs: ['', ''], dayMenu: null,
      gastos: [
        { id: 'g1', c: 'República Pagana', m: 4000, cat: 'Alojamiento', fecha: '30 jul.', ts: 1 },
        { id: 'g2', c: 'Bismarkcito', m: 600, cat: 'Comida', fecha: '30 jul.', ts: 2 },
        { id: 'g3', c: 'Museo de la ballena y Ciencias del Mar', m: 150, cat: 'Actividades', fecha: '30 jul.', ts: 3 },
        { id: 'g4', c: 'Casamarte Oyster Bar & Grill', m: 900, cat: 'Comida', fecha: '30 jul.', ts: 4 },
        { id: 'g5', c: 'The Home Depot', m: 375, cat: 'Compras', fecha: '30 jul.', ts: 5 },
        { id: 'g6', c: 'Mariscos El Molinito', m: 1200, cat: 'Comida', fecha: '30 jul.', ts: 6 },
        { id: 'g7', c: 'Dunas de Mogote', m: 150, cat: 'Gasolina', fecha: '30 jul.', ts: 7 }
      ],
      gastosOpen: true, expSort: 'fecha', budget: 9000, budgetEdit: false, budgetTxt: '',
      expFormOpen: false, expC: '', expM: '', expCat: 'Comida',
      desglose: false, desgTab: 'cat', heroMenuOpen: false,
      tplOpen: null, tplTab: 0, tplExp: {}, tplSel: {},
      dayItems: [
        [
          { uid: 'a1', name: 'República Pagana', horario: '', costo: 4000, travel: { mode: 'walk', t: '19 min · 1,6 km' }, nota: 'Since the moment I saw the murals and that terrace with its curvy, thin, and white walls that convey a shell-like feeling, as well as the murals and the whimsical furniture, I was drawn to select this hotel as my bunker for the two days I intend to stay in La Paz. I reckon there is hardly anything similar to its finding, and it has beautiful views of the beach that one can go to just by walking for a few minutes.' },
          { uid: 'a2', name: 'Malecón La Paz', horario: '5:00 - 6:30', costo: 0, reacts: [{ e: '🐾', n: 1, mine: true }], travel: { mode: 'walk', t: '18 min · 1,5 km' }, nota: "I once woke up really early to roam and jog in a 'malecon' and ever since I had wanted to relive it. I have never visited this shore, so its 4-mile extension, the multiple metal statues placed on the shore, the restaurants on the opposite sidewalk of the street, the boilerplates, the boats and sailboats, the tide of the shore, as well as the rocks and white sand will be pleasing to the senses to appreciate as the sun rises shyly above the malecon since it faces northward." },
          { uid: 'a3', name: 'Bismarkcito', horario: '8:00 - 9:00', costo: 600, reacts: [{ e: '🐙', n: 1, mine: true }], travel: { mode: 'walk', t: '31 min · 2,6 km' }, nota: "The view is promising because one really gets to see life unfolding with the stunning view of the sea and the fresh air that carries the sea breeze's chill from all around, since the 'malecón' is at a bay and therefore mostly surrounded by water. I consider it good to get to know several seafood places, so we intend to get to know and taste this one." },
          { uid: 'a4', name: 'Museo de la Ballena y Ciencias Del Mar', horario: '9:30 - 11:00', costo: 150, travel: { mode: 'walk', t: '27 min · 2,1 km' }, nota: 'Since we have never seen a bunch of whale skeletons and an archaeological hub of whales and marine life, I find this interesting. I would like the museum to have fossils of ancient whales so that I could confirm that whales have gradually lost the limbs they had when they roamed the Earth millions of years ago.' },
          { uid: 'a5', name: 'Casamarte Oyster Bar & Grill', horario: '11:30 - 12:30', costo: 900, travel: { mode: 'car', t: '9 min · 7,1 km' }, nota: 'I intend to walk from place to place. Given that I assume I have an unlimited budget, I can go anywhere and always be hydrated and connected to everyone. Following my culinary tour, I intend to visit a seafood restaurant near the beach that stands out for its oysters. I want to defy my preferences because I have always stated clearly that I do not like oysters and similar seafood such as snails. Could this place shift my conception of oysters?' },
          { uid: 'a6', name: 'Walmart Cola de Ballena', horario: '13:00 - 14:30', costo: 0, travel: { mode: 'walk', t: '22 min · 1,7 km' }, nota: 'I wonder what life in La Paz BC is like, and I would also need to purchase little things to fulfill my needs, as well as come across stuff that I think may be useful or interesting. Going to stores such as Walmart or Home Depot is a type of activity that my grandma enjoys. This one may smell a little salty and damp because it is less than a kilometer from the shore and surrounded by other stores as well.' },
          { uid: 'a7', name: 'The Home Depot', horario: '14:30 - 15:30', costo: 275, travel: { mode: 'car', t: '8 min · 6,6 km' }, nota: 'I need a shovel and perhaps other hardware. Why? I want to replicate the pyramids in Egypt. I would also like to make the statue of the lion whose name I do not know. Would someone be willing to be buried in a Lion or Cat statue? We intend to do that after eating beside the «Mogote» dunes.' },
          { uid: 'a8', name: 'Mariscos El Molino', horario: '16:00 - 17:00', costo: 1200, travel: { mode: 'car', t: '27 min · 31 km' }, nota: "Would we have to drive really fast? I asked myself this, but it isn't too far away from Home Depot in the car. This place is a bit more posh. The octopus caught my attention. You can see it has 8 fried tentacles in the photos, along with other tasty and nutritious food. Something different, and we would need to eat as this welcomes.\n• It is parallel to the dunes, which is where we would go next.\n• It's odd that what appears to be an oasis is on the opposite side of the bay, and there are wet golf fields." },
          { uid: 'a9', name: 'Dunas El Mogote', horario: '17:30 - 20:30', costo: 150, travel: null, nota: 'Weird. Look at the map. We could walk across the land from shore to shore. In between, there would be a lot of dunes. It would be reminiscent of the movies of Dune where Zendaya and the other guy appear with blue eyes. We should get sandboards because we would have more fun and commute faster by sliding down the dunes. I assume the tide would be calmer on the bay than on the gulf.\n\nI wonder how much the width of the passage changes as the tides ebb and flow because I remember that at Golfo the water receded a lot. While this happens, the sun would be setting on the west, almost in line with the shore.' }
        ],
        [
          { uid: 'b1', name: 'MTB Descenso Single Saca Muelas', horario: '', costo: 0, travel: { mode: 'car', t: '19 min · 15 km' }, nota: '' },
          { uid: 'b2', name: 'NEMI', horario: '', costo: 0, travel: { mode: 'walk', t: '9 min · 770 m' }, nota: '' },
          { uid: 'b3', name: 'Museo Regional de Antropología e Historia de Baja California Sur', horario: '', costo: 0, travel: { mode: 'walk', t: '13 min · 1,1 km' }, nota: '' },
          { uid: 'b4', name: 'Restaurante Central 1535', horario: '', costo: 0, travel: { mode: 'walk', t: '10 min · 810 m' }, nota: '' },
          { uid: 'b5', name: 'KAHE SUSHI La Paz', horario: '', costo: 0, travel: { mode: 'car', t: '30 min · 25 km' }, nota: '' },
          { uid: 'b6', name: 'Playa Balandra', horario: '', costo: 0, travel: { mode: 'car', t: '14 min · 4,9 km' }, nota: '' },
          { uid: 'b7', name: 'Playa El Tesoro', horario: '', costo: 0, travel: null, nota: '' }
        ]
      ],
      lists: [
        { id: 'l1', title: 'My notes', type: 'note', open: true, text: "I have never gone to La Paz, and prior to the creation of this plan, the only things I had heard about were chatter of foods and stormy weather during summer. I did not know it was the capital of 'Baja California Sur', and it is farther to the south than Culiacán in Sinaloa. I don't usually find myself in such low latitudes, which makes me wonder whether it tends to be more humid.\n\nI did not expect Baja California Sur to be so sparsely populated having human settlements that aren't so numerous. I assumed it was named 'La Paz' because of the bay and the calmness of the water, as well as the seclusion the geographical location provides.\n\nI haven't gone to the sea in 2 years, and I believe that driving to this city and following this 2-day itinerary, as well as visiting other beaches, cities, and attractions, would be nice. There are super tall and sturdy Sahuaros that are said to have lived for more than a century, and hiking is something that I am drawn to do because it fits with my morning habits.\n\nThese two days are characterized by a selection of three restaurants and attractions that we can appreciate before and after each mealtime. During the two days, we would visit what could be the 6 most relevant restaurants and attractions for me. My lobby would be the hotel I choose, which I liked a lot, and I set it as the first destination so that some of my belongings and my bed stay there.\n\nI consider that two days are not enough. I could go to the movie theater, use kayaks, contemplate the mangroves, go to bars, visit parks, drive around the suburbs, visit more museums." },
        { id: 'l2', title: 'My foreseen items', type: 'check', open: true, cardTitle: 'My things', newTxt: '', placeTxt: '', items: [
          { t: 'Luggage with clothes', done: true },
          { t: 'My phones', done: true },
          { t: 'Toothbrush, toothpaste, mouth rinse.', done: false },
          { t: 'Sunscreen and lip moisturizer', done: false },
          { t: 'Deodorant.', done: false },
          { t: 'Two towels.', done: false },
          { t: 'Wallet with IDs and cards.', done: false },
          { t: 'Cash.', done: false },
          { t: 'Dignity.', done: false },
          { t: 'Watch (The wrist clock, not the verb.)', done: false }
        ] }
      ],
      listMenu: null,
      dayCk: { title: 'Take care of and do not forget', newTxt: '', items: [
        { t: "Don't lose the shovel", done: false },
        { t: "Don't place your sandals or shoes too close to the sea because they could be washed away, and get lost. Bring them back.", done: true },
        { t: 'Bring the photo of the Egyptian pyramids. I mean, take the photos and share them.', done: true },
        { t: "Sunglasses because they're cool. Sunglass? Well, if you pretend to be a type of pirate.", done: false },
        { t: "Don't lose your earbuds or keys like I did.", done: false }
      ] },
      dayNote: "Has something similar taken place in people's lives? Having your clothes drenched and your feet wet and sandy. Feeling salty and the chills as the wind sneaks between your arms and ribs, or your ears and neck, and the sky is colored by a dark orange and a few scattered clouds. Having to place towels on the car seats because there weren't private spaces to change one's clothes. Are these moments worthwhile? Otherwise, I guess I wouldn't be relating this.\n\nOne usually uses shores as one straight line that extends indefinitely, but on the side of the bay it would gradually circle, beneath the arid mountains and a few mangroves. It would be interesting.",
      placeTxts: ['', ''],
      dayRecsOpen: { 0: true, 1: true },
      drag: null, dragOver: null, emoPicker: null,
      exQ: '', skelGuides: false, skelPlaces: false,
      // Explorar: qué secciones enseñan los 10 y en qué reseña va cada
      // carrusel (por place_id)
      exMas: {}, rvwIdx: {},
      resIdx: 19, narrow: false, mobile: false, mapModal: false, winW: 1440,
      // Listas del Resumen (Wanderlog): menú de nueva lista, nota en
      // edición, arrastre de artículos y foco del buscador de lugar
      newListMenu: false, noteEdit: null, ckDrag: null, ckDragOver: null, placeFocusId: null,
      // Tarjeta del itinerario desplegada (muestra "Añadir" y "Añadir costo")
      itemOpen: null,
      // Horario y gasto de un lugar (borradores hasta pulsar Guardar)
      horaMenu: null, hIni: '', hFin: '', hCual: 'ini',
      gastoMenu: null, gMonto: '', gMoneda: 'MXN', gCat: '', gDesc: '', gModo: 'no',
      gRep: {}, gMonOpen: false, gCatOpen: false, gModoOpen: false, gDonaOpen: true, gColorUid: null,
      // Título editable, horarios desplegables y autocompletado de lugares
      titleEdit: false, detHoursOpen: false, acKey: null, acItems: [],
      // Reseñas: cuáles están expandidas y cuántas se muestran
      rvwOpen: {}, rvwShown: 5,
      // Selector de emojis: búsqueda, categoría activa, en qué día
      // está el lugar al que se reacciona y dónde colocar el panel
      emoQ: '', emoCat: 0, emoDi: null, emoPos: null
    };
    this._boot();   // siembra desde window.PLAN_BOOT (Ruta Nómada) — sin servidor conserva el demo
  }
  // ════ Integración Ruta Nómada: siembra + persistencia ════
  _boot() {
    window.__plComp = this;   // handle de depuración
    const B = window.PLAN_BOOT;
    this.CSRF = window.PLAN_CSRF || '';
    this.USER = window.PLAN_USER || { inicial: 'R', nombre: 'Ramon' };
    this.MIEMBROS = [{ uid: Number(this.USER.id) || 0, inicial: this.USER.inicial, nombre: this.USER.nombre, rol: 'propietario', foto: null }];
    this.puedeEditar = true;   // por defecto (modo demo sin servidor)
    this.META = { titulo: 'Nuestro viaje a Ensenada', destino: 'Ensenada', fechas: '30/7 – 31/7', hero: 'https://picsum.photos/seed/rn-en-hero/1400/460' };
    this.DESC_ENSENADA = 'Ensenada es una ciudad portuaria de Baja California, a hora y media de la frontera, famosa por su malecón frente a la bahía de Todos Santos, sus tacos de pescado y su cercanía con el Valle de Guadalupe, la principal región vinícola de México. Al sur, La Bufadora lanza chorros de mar de más de 20 metros; en el centro, la Primera y la Plaza Cívica concentran cantinas históricas, cafés y una escena gastronómica que mezcla mariscos de la lonja con cocina de autor.';
    if (!B || !B.plan) { this.DAYS = this._mkDays('2026-07-30', '2026-07-31', 2); this.state.destinoDesc = this.DESC_ENSENADA; return; }
    const P = B.plan;
    this.PLAN_ID = Number(P.id);
    this.ROL = B.rol || 'lector';
    this.puedeEditar = this.ROL === 'editor' || this.ROL === 'propietario';
    let maxDia = 1;
    (B.items || []).forEach(it => { if (Number(it.dia) > maxDia) maxDia = Number(it.dia); });
    this.DAYS = this._mkDays(P.fecha_inicio, P.fecha_fin, maxDia);
    const N = this.DAYS.length;
    const dayItems = Array.from({ length: N }, () => []);
    (B.items || []).slice().sort((a, b) => (a.dia - b.dia) || (a.orden - b.orden) || (a.id - b.id)).forEach(it => {
      const di = Math.min(N, Math.max(1, Number(it.dia) || 1)) - 1;
      const h0 = (it.hora || '').slice(0, 5), h1 = (it.hora_fin || '').slice(0, 5);
      dayItems[di].push({
        uid: 'i' + it.id, sid: Number(it.id),
        name: it.nombre, nota: it.nota || '',
        horario: h0 ? (h1 ? h0 + ' - ' + h1 : h0) : '',
        costo: Number(it.precio) || 0, travel: null,
        // El horario también se guarda suelto: la cadena 'horario' es
        // para pintar, pero para editarlo hacen falta los dos extremos.
        hora: (it.hora || '').slice(0, 5), horaFin: (it.hora_fin || '').slice(0, 5),
        moneda: it.moneda || 'MXN', gastoCat: it.gasto_cat || '',
        gastoDesc: it.gasto_desc || '', gastoModo: it.gasto_modo || 'no',
        reparto: Array.isArray(it.reparto) ? it.reparto.map(r => ({ uid: Number(r.uid), monto: Number(r.monto) || 0, color: r.color || '' })) : [],
        // Cómo se va de este lugar al siguiente. null = el de por defecto.
        modo: it.modo_viaje || null,
        reacts: Array.isArray(it.reacts) ? it.reacts.map(r => ({ e: r.e, n: Number(r.n) || 0, mine: !!r.mine })) : [],
        pid: it.place_id || null,
        lat: (it.lat === null || it.lat === undefined) ? null : Number(it.lat),
        lng: (it.lng === null || it.lng === undefined) ? null : Number(it.lng),
        img: it.imagen_url || null, catg: it.categoria || 'custom'
      });
    });
    const gastos = (B.gastos || []).map((g, i) => ({
      id: Number(g.id), c: g.concepto, m: Number(g.monto) || 0, cat: g.categoria || 'Otro',
      fecha: this._fmtDia(g.fecha), fiso: g.fecha || '', ts: (B.gastos.length - i)
    }));
    const lists = (B.listas || []).map(L => ({
      id: Number(L.id), title: L.titulo, type: L.tipo === 'nota' ? 'note' : 'check',
      open: true, cardTitle: L.titulo, newTxt: '', placeTxt: '',
      text: L.texto || '',
      items: (L.items || []).map(x => ({ iid: Number(x.id), t: x.texto, done: !!Number(x.hecho) }))
    }));
    this.MIEMBROS = (B.miembros || []).map(m => ({
      uid: Number(m.usuario_id),
      inicial: (m.nombre || '?').trim().charAt(0).toUpperCase(),
      nombre: ((m.nombre || '') + ' ' + (m.apellidos || '')).trim(), rol: m.rol, foto: m.foto_perfil || null
    }));
    if (!this.MIEMBROS.length) this.MIEMBROS = [{ uid: Number(this.USER.id) || 0, inicial: this.USER.inicial, nombre: this.USER.nombre, rol: 'propietario', foto: null }];
    const dest = P.destino || 'mi destino';
    this.META = {
      titulo: P.nombre || ('Nuestro viaje a ' + dest),
      destino: dest,
      fechas: this._fmtRango(P.fecha_inicio, P.fecha_fin),
      hero: P.portada_url || ('https://picsum.photos/seed/rn-' + encodeURIComponent(dest.toLowerCase().replace(/\s+/g, '-')) + '-hero/1400/460')
    };
    this.CHIPS_SUG = [
      'Mejores lugares para comer en ' + dest,
      'Itinerario de ' + N + (N === 1 ? ' día en ' : ' días en ') + dest,
      'Principales atracciones en ' + dest
    ];
    // Explorar se llena con Google Places al abrirse (M2);
    // 'added' se siembra cruzando place_id con los items del plan,
    // guardando el id de servidor para que des-añadir borre en BD
    this.PLACES = [];
    this.POIS = [];
    this.RECS_REAL = [];
    this.SEARCH = [];
    this._placesLoaded = false;
    this._addedSid = {};
    const added = {};
    (B.items || []).forEach(it => {
      if (it.place_id) { added[it.place_id] = true; this._addedSid[it.place_id] = Number(it.id); }
    });
    const nMap = (v) => { const o = {}; for (let i = 0; i < N; i++) o[i] = v; return o; };
    const subs = Array.isArray(P.dia_subtitulos) ? P.dia_subtitulos.slice(0, N) : [];
    while (subs.length < N) subs.push('');
    Object.assign(this.state, {
      dayItems, gastos, lists,
      budget: Number(P.presupuesto) || 0,
      daySubs: subs, placeTxts: Array.from({ length: N }, () => ''),
      dayOpen: nMap(true), dayRecsOpen: nMap(true),
      added: added, dayCk: null, dayNote: '', chatLog: [],
      destinoDesc: dest === 'Ensenada' ? this.DESC_ENSENADA : ''
    });
  }
  _mkDays(fi, ff, minN) {
    const DS = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const DL = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const ML = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const MA = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    const COLS = ['#41A24D', '#6F42C1', '#1E86D8', '#E8365D', '#E7AD00', '#12808C', '#8e44ad'];
    const parse = (x) => { if (!x || x === '0000-00-00') return null; const a = String(x).split('-'); return a.length === 3 && +a[0] ? new Date(+a[0], +a[1] - 1, +a[2]) : null; };
    const out = [];
    const d0 = parse(fi); let d1 = parse(ff);
    if (d0) {
      if (!d1 || d1 < d0) d1 = d0;
      const cur = new Date(d0);
      while (cur <= d1 && out.length < 30) {
        out.push({
          label: DS[cur.getDay()] + '. ' + cur.getDate() + '/' + (cur.getMonth() + 1),
          num: String(cur.getDate()), mes: MA[cur.getMonth()],
          title: DL[cur.getDay()] + ', ' + cur.getDate() + ' de ' + ML[cur.getMonth()],
          iso: cur.getFullYear() + '-' + String(cur.getMonth() + 1).padStart(2, '0') + '-' + String(cur.getDate()).padStart(2, '0'),
          color: COLS[out.length % COLS.length]
        });
        cur.setDate(cur.getDate() + 1);
      }
    }
    while (out.length < Math.max(1, minN || 1)) {
      out.push({ label: 'Día ' + (out.length + 1), num: String(out.length + 1), mes: 'DÍA', title: 'Día ' + (out.length + 1), iso: null, color: COLS[out.length % COLS.length] });
    }
    return out;
  }
  _fmtDia(iso) {
    if (!iso) return '';
    const a = String(iso).split('-');
    if (a.length !== 3 || !+a[0]) return String(iso);
    const MA = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.'];
    return (+a[2]) + ' ' + MA[(+a[1]) - 1];
  }
  _fmtRango(fi, ff) {
    const p = (x) => { if (!x || x === '0000-00-00') return null; const a = String(x).split('-'); return a.length === 3 && +a[0] ? { d: +a[2], m: +a[1] } : null; };
    const a = p(fi), b = p(ff);
    if (!a) return 'Fechas por definir';
    if (!b || (a.d === b.d && a.m === b.m)) return a.d + '/' + a.m;
    return a.d + '/' + a.m + ' – ' + b.d + '/' + b.m;
  }
  // fetch de persistencia (optimista, fuego-y-olvida con aviso en consola)
  _sync(endpoint, body, onOk) {
    if (!this.PLAN_ID) return;   // modo demo sin servidor
    body = Object.assign({ plan_id: this.PLAN_ID }, body);
    fetch('api/' + endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': this.CSRF },
      body: JSON.stringify(body)
    }).then(r => r.json()).then(j => {
      if (!j.ok) console.warn('[plan] ' + endpoint + ':', j.error);
      else if (onOk) onOk(j);
    }).catch(err => console.warn('[plan] ' + endpoint + ':', err));
  }
  _syncSubs() {
    clearTimeout(this._subsT);
    this._subsT = setTimeout(() => this._sync('plan_update.php', { dia_subtitulos: this.state.daySubs }), 800);
  }
  // ════ Autocompletado de lugares (Google Places Autocomplete) ════
  //  Sugerencias mientras se escribe en "Añadir lugar"; al elegir una,
  //  el lugar entra al itinerario con sus coordenadas y su pin.
  _acBuscar(key, q) {
    clearTimeout(this._acT);
    const txt = (q || '').trim();
    if (txt.length < 2) { this.setState({ acKey: key, acItems: [] }); return; }
    this._acT = setTimeout(() => {
      if (!window.google || !google.maps || !google.maps.places) return;
      if (!this._acSvc) this._acSvc = new google.maps.places.AutocompleteService();
      if (!this._acTok) this._acTok = new google.maps.places.AutocompleteSessionToken();
      const req = { input: txt, sessionToken: this._acTok };
      const B = window.PLAN_BOOT;
      if (B && B.plan && B.plan.lat) {
        try {
          req.location = new google.maps.LatLng(Number(B.plan.lat), Number(B.plan.lng));
          req.radius = 50000;
        } catch (e) {}
      }
      this._acSvc.getPlacePredictions(req, (preds, st) => {
        const items = ((st === 'OK' && preds) ? preds : []).slice(0, 6).map(pr => ({
          pid: pr.place_id,
          main: pr.structured_formatting ? pr.structured_formatting.main_text : pr.description,
          sec: pr.structured_formatting ? (pr.structured_formatting.secondary_text || '') : ''
        }));
        this.setState({ acKey: key, acItems: items });
      });
    }, 260);
  }
  // Elegir una sugerencia: pide los datos y lo añade donde corresponda
  _acElegir(key, pid, nombre, alDia) {
    this.setState({ acKey: null, acItems: [] });
    if (!window.google || !google.maps || !google.maps.places) { alDia(nombre, {}); return; }
    const svc = new google.maps.places.PlacesService(this._map || document.createElement('div'));
    const opts = { placeId: pid, fields: ['name', 'geometry', 'photos'] };
    if (this._acTok) opts.sessionToken = this._acTok;
    svc.getDetails(opts, (r, st) => {
      this._acTok = null;   // el token de sesión se consume al pedir detalles
      if (st !== 'OK' || !r) { alDia(nombre, { gpid: pid }); return; }
      const loc = r.geometry && r.geometry.location;
      alDia(r.name || nombre, {
        gpid: pid,
        lat: loc ? loc.lat() : null,
        lng: loc ? loc.lng() : null,
        foto: (r.photos && r.photos[0]) ? r.photos[0].getUrl({ maxWidth: 400, maxHeight: 300 }) : ''
      });
    });
  }
  // Alta de un lugar en un día del itinerario (usado por el buscador y por
  // los botones "Añadir" de Explorar)
  _addItemDia(di, name, extra) {
    extra = extra || {};
    const tmpUid = 'p' + Date.now();
    const days = this.state.dayItems.map(x => [...x]);
    if (!days[di]) return;
    days[di] = [...days[di], {
      uid: tmpUid, name: name, nota: '', horario: '', costo: 0, travel: null, reacts: [],
      pid: extra.gpid || null,
      lat: (extra.lat === undefined || extra.lat === null) ? null : Number(extra.lat),
      lng: (extra.lng === undefined || extra.lng === null) ? null : Number(extra.lng),
      img: extra.foto || null
    }];
    this.setState({ dayItems: days });
    this._reproject();
    this._sync('plan_items.php', {
      action: 'add', dia: di + 1, nombre: name,
      categoria: extra.gpid ? 'hacer' : 'custom',
      place_id: extra.gpid || '',
      lat: (extra.lat === undefined || extra.lat === null) ? '' : extra.lat,
      lng: (extra.lng === undefined || extra.lng === null) ? '' : extra.lng,
      imagen_url: extra.foto || ''
    }, (j) => {
      const arr = this.state.dayItems.map(x => x.map(y => ({ ...y })));
      const it = arr[di] && arr[di].find(y => y.uid === tmpUid);
      if (it) { it.sid = Number(j.id); it.uid = 'i' + j.id; this.setState({ dayItems: arr }); }
      if (extra.gpid) {
        this._addedSid = this._addedSid || {};
        this._addedSid[extra.gpid] = Number(j.id);
        this.setState({ added: { ...this.state.added, [extra.gpid]: true } });
      }
      this._reproject();
    });
  }
  // ── Listas del Resumen: crear, enfocar y autoguardar (estilo Wanderlog) ──
  _addLista(tipo) {
    const esNota = tipo === 'nota';
    const tmpId = 'l' + Date.now();
    const titulo = esNota ? 'Nota' : 'Nueva lista';
    const nueva = {
      id: tmpId, title: titulo, type: esNota ? 'note' : 'check', open: true,
      cardTitle: esNota ? titulo : 'Mi lista', newTxt: '', placeTxt: '', text: '', items: []
    };
    this.setState({
      lists: [...this.state.lists, nueva],
      newListMenu: false,
      noteEdit: esNota ? tmpId : this.state.noteEdit
    });
    const enfocar = (id) => setTimeout(() => {
      if (esNota) this._focusNota(id, 0);
      else { const el = document.getElementById('rnNew' + id); if (el) el.focus(); }
    }, 40);
    if (!this.PLAN_ID) { enfocar(tmpId); return; }
    this._sync('plan_listas.php', { action: 'add', tipo: esNota ? 'nota' : 'check', titulo: titulo }, (j) => {
      const rid = Number(j.id);
      this.setState({
        lists: this.state.lists.map(x => (x.id === tmpId ? { ...x, id: rid } : x)),
        noteEdit: this.state.noteEdit === tmpId ? rid : this.state.noteEdit
      });
      enfocar(rid);
    });
  }
  _focusNota(id, off) {
    const el = document.getElementById('rnNote' + id);
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
    el.focus();
    const pos = (off === null || off === undefined) ? el.value.length : Math.min(off, el.value.length);
    try { el.setSelectionRange(pos, pos); } catch (e) {}
  }
  // "Añadir un lugar" del menú: enfoca el buscador de lugar de la última
  // lista de verificación; si no hay ninguna, crea una primero.
  _enfocarLugar() {
    const checks = this.state.lists.filter(x => x.type === 'check');
    if (!checks.length) { this._addLista('check'); return; }
    const id = checks[checks.length - 1].id;
    setTimeout(() => {
      const el = document.getElementById('rnPlace' + id);
      if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    }, 40);
  }
  _syncNota(id, texto) {
    clearTimeout(this._notaT);
    if (typeof id !== 'number') return;
    this._notaT = setTimeout(() => this._sync('plan_listas.php', { action: 'set_texto', id: id, texto: texto }), 800);
  }
  _syncListaTitulo(id, titulo) {
    clearTimeout(this._listaT);
    this._listaT = setTimeout(() => { if (titulo.trim()) this._sync('plan_listas.php', { action: 'rename', id: id, titulo: titulo.trim() }); }, 800);
  }
  _reloadListas() {
    if (!this.PLAN_ID) return;
    fetch('api/plan_get.php?id=' + this.PLAN_ID).then(r => r.json()).then(j => {
      if (!j.ok) return;
      const prev = this.state.lists;
      const lists = (j.listas || []).map(L => {
        const old = prev.find(x => Number(x.id) === Number(L.id));
        return {
          id: Number(L.id), title: L.titulo, type: L.tipo === 'nota' ? 'note' : 'check',
          open: old ? old.open : true, cardTitle: L.titulo,
          newTxt: old ? old.newTxt : '', placeTxt: old ? old.placeTxt : '',
          text: L.texto || '',
          items: (L.items || []).map(x => ({ iid: Number(x.id), t: x.texto, done: !!Number(x.hecho) }))
        };
      });
      this.setState({ lists });
    }).catch(() => {});
  }
  // ════ Google Maps (M1): mapa real bajo los overlays del prototipo ════
  _initMap() { this._gmapsOk = true; this._ensureMap(); }
  _ensureMap() {
    if (!this._gmapsOk || !window.google || !google.maps) return;
    const node = document.getElementById('rnGmap');
    if (!node) return;                                   // panel desmontado (narrow)
    if (this._map && this._mapNode === node) return;     // sin cambios
    // el sc-if de mapVisible desmonta/remonta el panel: re-crear el mapa
    // sobre el nuevo nodo conservando centro/zoom
    const prev = this._map ? { c: this._map.getCenter(), z: this._map.getZoom() } : null;
    const B = window.PLAN_BOOT || {};
    const hasLL = B.plan && B.plan.lat !== null && B.plan.lat !== undefined && B.plan.lat !== '';
    const center = prev ? { lat: prev.c.lat(), lng: prev.c.lng() }
      : hasLL ? { lat: Number(B.plan.lat), lng: Number(B.plan.lng) }
      : { lat: 23.6345, lng: -102.5528 };                // México
    const zoom = prev ? prev.z : (hasLL ? 13 : 5);
    this._mapNode = node;
    this._map = new google.maps.Map(node, {
      center, zoom, disableDefaultUI: true, clickableIcons: false
    });
    window.__plMap = this._map;                          // handle de depuración
    window.__plDiag = { init: Date.now(), reproj: 0, proj: null, pins: 0 };
    const comp = this;
    // El OverlayView solo sirve de disparador extra de redibujo; la
    // proyección se calcula con Mercator puro (no depende de que Maps
    // complete su primer ciclo de pintado)
    class Proyector extends google.maps.OverlayView {
      onAdd() {} onRemove() {}
      draw() { comp._reproject(); }
    }
    this._proy = new Proyector();
    this._proy.setMap(this._map);
    this._map.addListener('bounds_changed', () => this._reproject());
    this._map.addListener('center_changed', () => this._reproject());
    this._map.addListener('zoom_changed', () => this._reproject());
    this._map.addListener('drag', () => this._reproject());
    setTimeout(() => this._reproject(), 400);
    if (!prev && !hasLL && B.plan && B.plan.destino) {
      new google.maps.Geocoder().geocode({ address: B.plan.destino }, (res, st) => {
        if (st === 'OK' && res[0]) {
          const loc = res[0].geometry.location;
          B.plan.lat = loc.lat(); B.plan.lng = loc.lng();
          this._map.setCenter(loc); this._map.setZoom(13);
          this._sync('plan_update.php', { lat: loc.lat(), lng: loc.lng() });
          setTimeout(() => this.fitAllPins(true), 700);
        }
      });
    } else if (!prev) {
      setTimeout(() => this.fitAllPins(true), 700);
    }
    // El mapa se acaba de (re)crear: las polilíneas viejas quedaron
    // huérfanas, así que se fuerza el redibujado de todos los días.
    this._dayKey = [];
    this._polys = [];
    this._updateRoute();
  }
  // ════ Google Places (M2): Explorar con lugares reales ════
  _loadPlaces() {
    if (this._placesLoading || this._placesLoaded || !this.PLAN_ID) return;
    if (!window.google || !google.maps || !google.maps.places) {
      if (window.gmapsReady) window.gmapsReady.then(() => setTimeout(() => this._loadPlaces(), 250));
      return;
    }
    this._placesLoading = true;
    const dest = this.META.destino || '';
    const KEY = 'rn_places_' + dest.toLowerCase();
    // caché de sesión (TTL 1 h) — evita repetir textSearch al recargar
    try {
      const c = JSON.parse(sessionStorage.getItem(KEY));
      if (c && c.t && Date.now() - c.t < 3600e3 && Array.isArray(c.places)) {
        // diferir un tick: go('explorar') hace su propio setState(skelPlaces:true)
        // DESPUÉS de llamarnos; si aplicáramos síncrono, lo pisaría y el
        // skeleton quedaría atascado
        setTimeout(() => this._applyPlaces(c.places, c.recs || []), 0);
        return;
      }
    } catch (e) {}
    const svc = new google.maps.places.PlacesService(this._map || document.createElement('div'));
    const TIPOS = {
      restaurant: 'Restaurante', food: 'Comida', meal_takeaway: 'Para llevar', cafe: 'Café',
      bar: 'Bar', bakery: 'Panadería', tourist_attraction: 'Atracción', museum: 'Museo',
      park: 'Parque', natural_feature: 'Naturaleza', amusement_park: 'Diversiones',
      aquarium: 'Acuario', art_gallery: 'Galería', zoo: 'Zoológico', church: 'Iglesia',
      shopping_mall: 'Centro comercial', night_club: 'Club nocturno', store: 'Tienda',
      lodging: 'Hotel', spa: 'Spa', winery: 'Vinícola'
    };
    const BUSQ = [
      { q: 'atracciones turísticas en ' + dest, sec: 'top', cat: 'atr' },
      { q: 'mejores restaurantes en ' + dest, sec: 'eat', cat: 'com' },
      // Alojamiento: la consulta nombra los tres tipos para que Google
      // no devuelva sólo cadenas hoteleras grandes.
      { q: 'hoteles, moteles y hostales en ' + dest, sec: 'stay', cat: 'hot' }
    ];
    const resultados = {};
    let pendientes = BUSQ.length;
    const listo = () => {
      if (--pendientes > 0) return;
      const places = []; const recs = [];
      BUSQ.forEach(b => {
        (resultados[b.sec] || []).forEach((r, i) => {
          const loc = r.geometry && r.geometry.location;
          const chipsAll = (r.types || []).map(t => TIPOS[t]).filter(Boolean);
          const foto = r.photos && r.photos[0]
            ? r.photos[0].getUrl({ maxWidth: 400, maxHeight: 300 })
            : 'https://picsum.photos/seed/rn-' + b.sec + '-' + i + '/300/240';
          const p = {
            id: r.place_id, gpid: r.place_id, num: i + 1, cat: b.cat, sec: b.sec,
            name: r.name, rating: Number(r.rating) || 0, rev: Number(r.user_ratings_total) || 0,
            chips: chipsAll.slice(0, 2), more: Math.max(0, chipsAll.length - 2),
            price: Number(r.price_level) || 0, seed: 'rn-' + b.sec + '-' + i,
            foto: foto, desc: '', rvw: null,
            lat: loc ? loc.lat() : null, lng: loc ? loc.lng() : null,
            recent: false
          };
          // 10 por sección: es lo que enseña "Mostrar más". Antes se
          // guardaban 6 y no había de dónde sacar los otros cuatro.
          if (i < Component.EX_MAS) places.push(p);
          else if (recs.length < 6) recs.push({ name: p.name, seed: p.seed, foto: foto, gpid: p.gpid, lat: p.lat, lng: p.lng });
        });
      });
      try { sessionStorage.setItem(KEY, JSON.stringify({ t: Date.now(), places, recs })); } catch (e) {}
      this._applyPlaces(places, recs);
    };
    BUSQ.forEach(b => {
      svc.textSearch({ query: b.q }, (res, st) => {
        resultados[b.sec] = (st === 'OK' && res) ? res.slice(0, 16) : [];
        if (st !== 'OK') console.warn('[plan] textSearch', b.sec, st);
        listo();
      });
    });
  }
  _applyPlaces(places, recs) {
    this.PLACES = places || [];
    this.RECS_REAL = recs || [];
    this._placesLoaded = true;
    this._placesLoading = false;
    this.setState({ skelPlaces: false });
    this._reproject();
  }
  // ════ M4: buscador del mapa ("Buscar en esta zona" + categorías) ════
  _mapSearch(query) {
    if (!this._map || !window.google || !google.maps.places) return;
    const q = (query || '').trim();
    if (!q) return;
    const svc = new google.maps.places.PlacesService(this._map);
    // getBounds() puede ser undefined si el mapa aún no completa su layout
    // (p. ej. pestaña en segundo plano): respaldo con centro + radio
    const req = { query: q };
    const b = this._map.getBounds();
    if (b) req.bounds = b;
    else { req.location = this._map.getCenter(); req.radius = 20000; }
    svc.textSearch(req, (res, st) => {
      if (st !== 'OK') console.warn('[plan] mapSearch', st);
      this.SEARCH = (st === 'OK' && res ? res.slice(0, 8) : []).map((r, i) => {
        const loc = r.geometry && r.geometry.location;
        return {
          id: r.place_id, gpid: r.place_id, num: i + 1, cat: 'sav', sec: 'search',
          name: r.name, rating: Number(r.rating) || 0, rev: Number(r.user_ratings_total) || 0,
          chips: [], more: 0, price: Number(r.price_level) || 0, seed: 'rn-srch-' + i,
          foto: r.photos && r.photos[0] ? r.photos[0].getUrl({ maxWidth: 400, maxHeight: 300 })
            : 'https://picsum.photos/seed/rn-srch-' + i + '/300/240',
          desc: '', rvw: null,
          lat: loc ? loc.lat() : null, lng: loc ? loc.lng() : null,
          recent: false
        };
      });
      this.setState({ mapSearchOpen: false });
      this._reproject();
    });
  }
  // pines proyectables: itinerario + lugares de Explorar cargados
  _pinList() {
    const out = [];
    const lc = this.state.layerChecks || {};
    (this.state.dayItems || []).forEach((arr, di) => {
      if (lc['d' + di] === false) return;
      arr.forEach((it, i) => {
        if (it.lat == null || it.lng == null) return;
        out.push({ id: it.uid, lat: it.lat, lng: it.lng, day: di, num: i + 1, name: it.name });
      });
    });
    (this.PLACES || []).forEach(p => {
      if (p.lat == null || p.lng == null) return;
      // La capa se decide por la sección (top / eat / stay), no por la
      // categoría: con el mapeo viejo los hoteles se apagaban al quitar
      // "Mejores sitios para comer" y su propia casilla no hacía nada.
      if (lc[p.sec] === false) return;
      out.push({ id: p.id, lat: p.lat, lng: p.lng });
    });
    (this.SEARCH || []).forEach(p => {
      if (p.lat == null || p.lng == null) return;
      if (out.some(x => x.id === p.id)) return;   // ya está como lugar de Explorar
      out.push({ id: p.id, lat: p.lat, lng: p.lng });
    });
    return out;
  }
  _reproject() {
    if (this._rafPend) return;
    this._rafPend = true;
    const run = () => {
      if (!this._rafPend) return;   // ya corrió (rAF o timeout, el primero gana)
      this._rafPend = false;
      if (!this._map || !this._mapNode) return;
      // Mercator puro: px = (mundo(punto) − mundo(centro)) · 2^zoom + centroDelDiv
      const MERC = (lat, lng) => {
        const siny = Math.min(Math.max(Math.sin(lat * Math.PI / 180), -0.9999), 0.9999);
        return { x: 256 * (0.5 + lng / 360), y: 256 * (0.5 - Math.log((1 + siny) / (1 - siny)) / (4 * Math.PI)) };
      };
      const c = this._map.getCenter();
      if (!c) return;
      const scale = Math.pow(2, this._map.getZoom());
      const cw = this._mapNode.offsetWidth / 2, ch = this._mapNode.offsetHeight / 2;
      if (!cw || !ch) return;
      const wc = MERC(c.lat(), c.lng());
      if (window.__plDiag) { window.__plDiag.reproj++; window.__plDiag.proj = true; window.__plDiag.pins = this._pinList().length; }
      const px = {}; const seen = {};
      for (const p of this._pinList()) {
        const wp = MERC(p.lat, p.lng);
        let x = Math.round((wp.x - wc.x) * scale + cw);
        let y = Math.round((wp.y - wc.y) * scale + ch);
        if (x < -60 || y < -60 || x > cw * 2 + 60 || y > ch * 2 + 60) continue;   // fuera de vista
        const key = x + ',' + y;                          // pines en la misma coordenada
        if (seen[key]) { x += 8 * seen[key]; seen[key]++; } else seen[key] = 1;
        px[p.id] = { left: x, top: y };
      }
      const prev = this._pinPx || {};
      const kn = Object.keys(px), kp = Object.keys(prev);
      const same = kn.length === kp.length && kn.every(k => prev[k] && prev[k].left === px[k].left && prev[k].top === px[k].top);
      this._pinPx = px;
      if (!same) this.setState({ _pinTick: (this.state._pinTick || 0) + 1 });
    };
    requestAnimationFrame(run);
    setTimeout(run, 120);   // respaldo: pestañas en 2º plano o paneles sin composición
  }
  // ════ Rutas del itinerario ════════════════════════════════════
  //  Una polilínea por TRAMO (par de lugares consecutivos), no una por
  //  día. Cuesta lo mismo de dibujar y es lo que permite que, al
  //  reordenar, los tramos que no cambiaron conserven su geometría en
  //  vez de recalcularse enteros.
  //
  //  Se usa google.maps.Polyline y no un SVG propio a pesar de que los
  //  pines van con proyección casera: la Polyline no depende de
  //  getProjection() (que es lo que fallaba con los pines), y además
  //  vive por debajo de ellos, así que nunca tapa un número.
  //  ⚠ Esto se sostiene mientras el mapa siga siendo ráster. Si algún
  //  día se le pone un mapId, pasa a vectorial con inclinación y giro,
  //  y MERC() —que no conoce el ángulo de cámara— dejaría de cuadrar.

  _rutaEstilo(color, firme) {
    // Firme = geometría real o recta conocida. No firme = provisional o
    // tramo que no se pudo resolver: punteado, que en la API de Maps no
    // es dasharray sino símbolos repetidos sobre una línea invisible.
    if (firme) {
      return { strokeColor: color, strokeOpacity: .95, strokeWeight: 4 };
    }
    return {
      strokeColor: color, strokeOpacity: 0, strokeWeight: 4,
      icons: [{
        icon: { path: google.maps.SymbolPath.CIRCLE, scale: 2.4,
                fillColor: color, fillOpacity: .85, strokeOpacity: 0 },
        offset: '0', repeat: '11px'
      }]
    };
  }

  _destruirDia(di) {
    const d = (this._polys || [])[di];
    if (!d) return;
    d.segs.forEach(sg => {
      if (sg.halo) sg.halo.setMap(null);
      if (sg.line) sg.line.setMap(null);
    });
    this._polys[di] = null;
  }

  _rebuildDay(di) {
    this._destruirDia(di);
    const arr = (this.state.dayItems || [])[di] || [];
    const color = this.DAYS[di] ? this.DAYS[di].color : '#F0B429';
    const segs = [];
    let faltan = false;

    for (let i = 0; i + 1 < arr.length; i++) {
      const a = arr[i], b = arr[i + 1];
      // Un lugar sin coordenadas NO se puede puentear: antes el filtro lo
      // descartaba en silencio y la línea unía a sus vecinos, dibujando
      // una ruta que nadie calculó. Mejor dejar el hueco visible.
      if (a.lat == null || a.lng == null || b.lat == null || b.lng == null) {
        segs.push({ halo: null, line: null, sinDatos: true });
        continue;
      }
      const recta = [{ lat: a.lat, lng: a.lng }, { lat: b.lat, lng: b.lng }];
      const k = this._segKey(a, b);
      const g = this._segs ? this._segs[k] : undefined;

      // Tres estados, y distinguirlos importa:
      //   sin entrada  → todavía no se ha pedido    → punteado + pedirlo
      //   entrada ok   → geometría real de carretera → sólido
      //   entrada !ok  → Google dijo que no hay ruta → punteado y NO volver
      //                  a pedirlo, o se pediría en cada carga para siempre
      if (g === undefined) faltan = true;
      const firme = !!(g && g.ok && g.pts && g.pts.length > 1);
      const path = firme ? g.pts : recta;

      // Halo blanco por debajo: sin él el color del día se pierde sobre
      // las carreteras amarillas del mapa y dos días que compartan calle
      // no se distinguen.
      const halo = new google.maps.Polyline({
        map: this._map, path, geodesic: false, clickable: false,
        strokeColor: '#FFFFFF', strokeOpacity: firme ? .9 : 0, strokeWeight: 7, zIndex: 1
      });
      const line = new google.maps.Polyline(Object.assign({
        map: this._map, path, geodesic: false, clickable: false, zIndex: 2
      }, this._rutaEstilo(color, firme)));

      segs.push({ halo, line, a: k, real: firme });
    }
    (this._polys = this._polys || [])[di] = { segs };
    // Los tramos que aún no conocemos se piden en diferido. No hay bucle:
    // al volver la respuesta quedan con entrada en _segs (con ok true o
    // false) y ya no vuelven a marcarse como pendientes.
    if (faltan && !this._rutasApagadas) this._rutaSucia(di);
  }

  // ── Texto del traslado entre dos lugares ──────────────────────
  //  Bajo un kilómetro se lee mejor en metros ("850 m" dice más que
  //  "0,9 km"); a partir de ahí, kilómetros con un decimal.
  _fmtDist(m) {
    if (m == null) return '';
    // Redondear ANTES de decidir la unidad: si no, 995 m se redondea a
    // 1000 y se imprimiría "1000 m", contradiciendo la propia regla.
    const r = Math.round(m / 10) * 10;
    // Punto decimal, no coma: en México el separador decimal es el punto.
    return r < 1000 ? r + ' m' : (r / 1000).toFixed(1) + ' km';
  }
  _fmtDur(s) {
    if (s == null) return '';
    const min = Math.round(s / 60);
    if (min < 60) return min + ' min';
    const h = Math.floor(min / 60), r = min % 60;
    return r ? h + ' h ' + r + ' min' : h + ' h';
  }
  // Modo efectivo del tramo que sale de este lugar
  _modoDe(it) { return it && it.modo ? it.modo : 'DRIVE'; }

  // Cambiar el medio de transporte de un tramo. Cambia la clave del tramo,
  // así que la geometría se vuelve a pedir sola (y si ya se pidió antes con
  // ese modo, sale de la caché sin gastar nada).
  _setModo(di, it, modo) {
    const days = this.state.dayItems.map(a => [...a]);
    const j = (days[di] || []).findIndex(x => x.uid === it.uid);
    if (j < 0) return;
    days[di][j] = { ...days[di][j], modo };
    this.setState({ dayItems: days, modoMenu: null });
    if (it.sid) this._sync('plan_items.php', { action: 'update', id: it.sid, modo_viaje: modo });
    this._rutaSucia(di);
  }

  // La clave lleva el modo: el mismo par a pie y en coche son dos rutas
  // distintas. Debe coincidir con rutaClave()/hash de api/ruta.php.
  _segKey(a, b) {
    const id = (x) => x.pid || (x.lat != null ? x.lat.toFixed(5) + ',' + x.lng.toFixed(5) : 'x');
    return this._modoDe(a) + '|' + id(a) + '>' + id(b);
  }

  // Visibilidad: barato, se recalcula siempre. NUNCA con setMap(null),
  // que desvincula el overlay y lo rehace entero al volver.
  _applyRouteVisibility() {
    const s = this.state, lc = s.layerChecks || {};
    (this._polys || []).forEach((d, di) => {
      if (!d) return;
      const on = !!s.routeLines
        && lc['d' + di] !== false
        && (s.rutaSolo === null || s.rutaSolo === undefined || s.rutaSolo === di);
      d.segs.forEach(sg => {
        if (sg.halo) sg.halo.setVisible(on);
        if (sg.line) sg.line.setVisible(on);
      });
    });
  }

  // ── Geometría real: pedirla, con freno y sin pisarse ──────────
  //  Al soltar un lugar arrastrado se marca el día como sucio. El
  //  debounce hace que tres arrastres seguidos sean UNA petición, no tres.
  _rutaSucia(...dias) {
    if (!this.PLAN_ID) return;
    this._sucios = this._sucios || {};
    dias.forEach(di => { if (di != null && di >= 0) this._sucios[di] = true; });
    clearTimeout(this._rutaT);
    this._rutaT = setTimeout(() => this._pedirRutas(), 400);
  }

  _pedirRutas() {
    const s = this.state;
    // _dayKey lo crea _updateRoute(), que no llega a ejecutarse si no hay
    // mapa (pantalla estrecha). Sin esta línea, pedir los tramos reventaba
    // antes del fetch y la lista se quedaba en "Calculando…" para siempre.
    this._dayKey = this._dayKey || [];
    const pend = Object.keys(this._sucios || {}).map(Number);
    this._sucios = {};
    pend.forEach(di => {
      const arr = (s.dayItems || [])[di] || [];
      // Se pide aunque la capa del día esté oculta en el mapa: el tiempo y
      // la distancia se leen en la lista del itinerario, que se ve siempre.
      const puntos = arr.filter(x => x.lat != null && x.lng != null)
        .map(x => ({ pid: x.pid || '', lat: x.lat, lng: x.lng, modo: this._modoDe(x) }));
      if (puntos.length < 2) return;

      // Sello del orden actual: si al volver la respuesta el día ya cambió,
      // se descarta en vez de pintar una ruta que ya no corresponde.
      const sello = this._dayKey[di];
      fetch('api/ruta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF': this.CSRF },
        body: JSON.stringify({ plan_id: this.PLAN_ID, dia: puntos, modo: 'DRIVE' })
      })
        .then(r => r.json())
        .then(j => {
          if (!j || !j.ok || !j.tramos) return;
          if (this._dayKey[di] !== sello) return;   // el usuario siguió moviendo
          // 'recta' significa que el servicio de rutas no estaba disponible
          // (sin clave, API apagada, tope alcanzado). NO son respuestas
          // autorizadas: guardarlas marcaría todos los tramos como
          // "sin ruta" para siempre y enseñaría tiempos inventados.
          // Se apaga para esta sesión y el mapa se queda con rectas.
          // setState y no sólo _updateRoute(): el tiempo y la distancia se
          // calculan al pintar la lista, así que hace falta un re-render.
          if (j.fuente === 'recta') { this._rutasApagadas = true; this.setState({ rutasN: (this.state.rutasN || 0) + 1 }); return; }
          this._segs = this._segs || {};
          j.tramos.forEach(t => {
            this._segs[t.k] = {
              ok: !!t.ok,
              pts: (t.pts || []).map(c => ({ lat: c[0], lng: c[1] })),
              m: t.m, s: t.s
            };
          });
          // Forzar el redibujado del mapa y, con el setState, el repintado
          // de la lista para que aparezcan el tiempo y la distancia.
          this._dayKey[di] = null;
          this.setState({ rutasN: (this.state.rutasN || 0) + 1 });
        })
        .catch(() => {});
    });
  }

  _updateRoute() {
    if (!this._map) return;
    const dias = this.state.dayItems || [];
    this._dayKey = this._dayKey || [];
    this._polys = this._polys || [];

    // Días que ya no existen (se borró el último)
    for (let di = dias.length; di < this._polys.length; di++) this._destruirDia(di);
    this._polys.length = dias.length;

    // Geometría: sólo se rehace el día cuyo orden o coordenadas cambiaron
    dias.forEach((arr, di) => {
      const k = arr.map(x => x.uid + '@' +
        (x.lat == null ? '-' : x.lat.toFixed(5) + ',' + x.lng.toFixed(5))).join('|');
      if (k !== this._dayKey[di]) { this._dayKey[di] = k; this._rebuildDay(di); }
    });

    this._applyRouteVisibility();
  }
  fitAllPins(first) {
    if (!this._map) return;
    const pins = this._pinList();
    if (!pins.length) return;
    if (pins.length === 1) {
      this._map.panTo({ lat: pins[0].lat, lng: pins[0].lng });
      this._map.setZoom(14);
      return;
    }
    const b = new google.maps.LatLngBounds();
    pins.forEach(p => b.extend({ lat: p.lat, lng: p.lng }));
    this._map.fitBounds(b, 70);
    if (first) {
      google.maps.event.addListenerOnce(this._map, 'idle', () => {
        if (this._map.getZoom() > 15) this._map.setZoom(15);
      });
    }
  }
  componentDidUpdate() {
    this._ensureMap();
    // Centrado que quedó en cola porque el mapa no existía todavía
    // (la ficha abierta en pantalla estrecha monta el mapa a la vez).
    // Va DESPUÉS de _ensureMap: al recrearse, el mapa conserva el
    // centro anterior, y sin esto enseñaría el sitio equivocado.
    if (this._map && this._centrarPend) {
      const p = this._centrarPend;
      this._centrarPend = null;
      this._map.panTo(p);
      if (this._map.getZoom() < 15) this._map.setZoom(15);
    }
    this._asegurarTramos();
    this._updateRoute();
    this._emoMontar();
  }

  // El tiempo y la distancia se enseñan en la LISTA del itinerario, que
  // existe aunque el mapa no esté montado (pantallas estrechas, o el panel
  // del mapa cerrado). Por eso pedir los tramos no puede depender del mapa:
  // si dependiera, en móvil pondría "Calculando…" para siempre.
  _asegurarTramos() {
    if (!this.PLAN_ID || this._rutasApagadas) return;
    (this.state.dayItems || []).forEach((arr, di) => {
      for (let i = 0; i + 1 < arr.length; i++) {
        const a = arr[i], b = arr[i + 1];
        if (a.lat == null || a.lng == null || b.lat == null || b.lng == null) continue;
        if (!this._segs || this._segs[this._segKey(a, b)] === undefined) { this._rutaSucia(di); return; }
      }
    });
  }
  place(id) {
    for (const p of this.PLACES) { if (p.id === id) return p; }
    for (const p of (this.SEARCH || [])) { if (p.id === id) return p; }
    // Lugares que ya están en el itinerario (por place_id o por uid):
    // así el panel del mapa también se abre desde sus tarjetas y pines
    const dias = this.state.dayItems || [];
    for (let di = 0; di < dias.length; di++) {
      const arr = dias[di] || [];
      for (let i = 0; i < arr.length; i++) {
        const it = arr[i];
        if ((it.pid && it.pid === id) || it.uid === id) return this._itemComoLugar(it, di, i);
      }
    }
    return null;
  }
  // En qué día del itinerario está el lugar que enseña la ficha, y en
  // qué posición. Es lo que recorre el paginador "n de n".
  _diaDelDetalle(id) {
    if (!id) return null;
    const dias = this.state.dayItems || [];
    for (let di = 0; di < dias.length; di++) {
      const arr = dias[di] || [];
      const i = arr.findIndex(x => (x.pid || x.uid) === id);
      if (i >= 0) return { di, i, arr };
    }
    return null;
  }
  // Centra el mapa en un lugar sin alejar lo que el usuario ya tenía.
  // Si el mapa aún no existe (pantalla estrecha: el panel se monta AL
  // abrir la ficha, no antes), el destino queda en cola y lo consume
  // componentDidUpdate en cuanto _ensureMap lo cree.
  _centrarEn(it) {
    if (!it || it.lat == null || it.lng == null) return;
    const p = { lat: Number(it.lat), lng: Number(it.lng) };
    if (!this._map) { this._centrarPend = p; return; }
    this._centrarPend = null;
    this._map.panTo(p);
    if (this._map.getZoom() < 15) this._map.setZoom(15);
  }
  // Adapta un item del itinerario a la forma que espera el panel de detalle
  _itemComoLugar(it, di, i) {
    const dd = (it.pid && this._detCache) ? this._detCache[it.pid] : null;
    return {
      id: it.pid || it.uid, gpid: it.pid || null,
      num: i + 1, cat: 'sav', sec: 'itin',
      pinColor: this.DAYS[di] ? this.DAYS[di].color : this.PIN.sav,
      name: it.name,
      rating: dd ? dd.rating : 0, rev: dd ? dd.rev : 0,
      chips: [], more: 0, price: 0,
      seed: 'rn-it-' + (it.sid || it.uid),
      foto: it.img || '', desc: '', rvw: null,
      lat: it.lat, lng: it.lng, recent: false
    };
  }
  // ════ Reacciones con emoji ════
  //  Cada persona tiene UNA reacción por lugar: elegir otro emoji
  //  reemplaza el propio; repetir el mismo lo quita. El contador sube
  //  solo cuando reaccionan personas distintas.
  reaccionar(di, uid, emoji) {
    const dias = this.state.dayItems.map(a => a.map(x => ({ ...x, reacts: (x.reacts || []).map(r => ({ ...r })) })));
    const it = (dias[di] || []).find(x => x.uid === uid);
    if (!it) return;
    const rs = it.reacts || [];
    const mio = rs.find(r => r.mine);
    const quitar = !!(mio && mio.e === emoji);
    if (mio) {                       // soltar la reacción anterior
      mio.n -= 1; mio.mine = false;
    }
    if (!quitar) {                   // poner la nueva
      const ya = rs.find(r => r.e === emoji);
      if (ya) { ya.n += 1; ya.mine = true; } else { rs.push({ e: emoji, n: 1, mine: true }); }
    }
    it.reacts = rs.filter(r => r.n > 0);
    this.setState({ dayItems: dias, emoPicker: null });
    if (it.sid) {
      this._sync('plan_reacciones.php', { item_id: it.sid, emoji: emoji }, (j) => {
        // el servidor manda el recuento real (puede incluir a otras personas)
        const arr = this.state.dayItems.map(a => a.map(x => ({ ...x })));
        const dest = (arr[di] || []).find(x => x.sid === it.sid);
        if (dest) { dest.reacts = j.reacts || []; this.setState({ dayItems: arr }); }
      });
    }
  }
  // ════════════ Selector de emojis ════════════
  // Geometría del panel (400x550). Las alturas tienen que cuadrar al
  // píxel con el CSS de .rn-emogrid: se usan para el
  // contain-intrinsic-size de cada sección, y si mienten, el
  // scrollHeight y los offsets dejan de ser reales y el subrayado
  // apuntaría a la categoría equivocada.
  static get EMO() { return { COLS: 8, CELDA: 44, CAB: 30, ANCHO: 400, ALTO: 550, TAB: 400 / 9, SUB: 26 }; }

  // Orden de las pestañas del frame de Figma. No es el del catálogo:
  // ahí Viajes (3) va antes que Actividad (4), y en el diseño el balón
  // va antes que el coche. Las secciones se pintan en este mismo orden
  // para que el subrayado y el scroll coincidan.
  _emoIconos() {
    if (this._emoIco) return this._emoIco;
    const F = (d, vb, w, h) => ({ d, vb, w: String(w), h: String(h), relleno: 'currentColor', trazo: 'none', grosor: '0' });
    this._emoIco = [
      // El reloj no venía entre los SVG de Font Awesome, así que va
      // dibujado con trazo (círculo + manecillas), como en el frame.
      { n: 'Usados frecuentemente', cat: -1, d: 'M21 12a9 9 0 1 1-18 0 9 9 0 1 1 18 0M12 7v5l3 2', vb: '0 0 24 24', w: '22', h: '22', relleno: 'none', trazo: 'currentColor', grosor: '2' },
      // Los ocho de Font Awesome. El viewBox va recortado a la caja
      // real del glifo (el descargado siempre es 0 0 640 640, con
      // relleno alrededor) y el eje mayor mide 22 px, como se pidió.
      { n: 'Caritas y personas', cat: 0, ...F('M528 320C528 205.1 434.9 112 320 112C205.1 112 112 205.1 112 320C112 434.9 205.1 528 320 528C434.9 528 528 434.9 528 320zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320zM241.3 383.4C256.3 399 282.4 416 320 416C357.6 416 383.7 399 398.7 383.4C407.9 373.8 423.1 373.5 432.6 382.7C442.1 391.9 442.5 407.1 433.3 416.6C411.2 439.6 373.3 464 320 464C266.7 464 228.8 439.6 206.7 416.6C197.5 407 197.8 391.8 207.4 382.7C217 373.6 232.2 373.8 241.3 383.4zM208 272C208 254.3 222.3 240 240 240C257.7 240 272 254.3 272 272C272 289.7 257.7 304 240 304C222.3 304 208 289.7 208 272zM372 280C372 291 363 300 352 300C341 300 332 291 332 280C332 246.9 358.9 220 392 220L408 220C441.1 220 468 246.9 468 280C468 291 459 300 448 300C437 300 428 291 428 280C428 269 419 260 408 260L392 260C381 260 372 269 372 280z', '64 64 512 512', 22, 22) },
      { n: 'Animales y naturaleza', cat: 1, ...F('M298.5 156.9C312.8 199.8 298.2 243.1 265.9 253.7C233.6 264.3 195.8 238.1 181.5 195.2C167.2 152.3 181.8 109 214.1 98.4C246.4 87.8 284.2 114 298.5 156.9zM164.4 262.6C183.3 295 178.7 332.7 154.2 346.7C129.7 360.7 94.5 345.8 75.7 313.4C56.9 281 61.4 243.3 85.9 229.3C110.4 215.3 145.6 230.2 164.4 262.6zM133.2 465.2C185.6 323.9 278.7 288 320 288C361.3 288 454.4 323.9 506.8 465.2C510.4 474.9 512 485.3 512 495.7L512 497.3C512 523.1 491.1 544 465.3 544C453.8 544 442.4 542.6 431.3 539.8L343.3 517.8C328 514 312 514 296.7 517.8L208.7 539.8C197.6 542.6 186.2 544 174.7 544C148.9 544 128 523.1 128 497.3L128 495.7C128 485.3 129.6 474.9 133.2 465.2zM485.8 346.7C461.3 332.7 456.7 295 475.6 262.6C494.5 230.2 529.6 215.3 554.1 229.3C578.6 243.3 583.2 281 564.3 313.4C545.4 345.8 510.3 360.7 485.8 346.7zM374.1 253.7C341.8 243.1 327.2 199.8 341.5 156.9C355.8 114 393.6 87.8 425.9 98.4C458.2 109 472.8 152.3 458.5 195.2C444.2 238.1 406.4 264.3 374.1 253.7z', '64 96 512 448', 22, 19.25) },
      { n: 'Comida y bebida', cat: 2, ...F('M320 176C311.2 176 304 168.8 304 160L304 144C304 99.8 339.8 64 384 64L400 64C408.8 64 416 71.2 416 80L416 96C416 140.2 380.2 176 336 176L320 176zM96 352C96 275.7 131.7 192 208 192C235.3 192 267.7 202.3 290.7 211.3C309.5 218.6 330.6 218.6 349.4 211.3C372.3 202.4 404.8 192 432.1 192C508.4 192 544.1 275.7 544.1 352C544.1 480 464.1 576 384.1 576C367.6 576 346 569.4 332.6 564.7C324.5 561.9 315.7 561.9 307.6 564.7C294.2 569.4 272.6 576 256.1 576C176.1 576 96.1 480 96.1 352z', '96 64 448 512', 19.25, 22) },
      { n: 'Actividad', cat: 4, ...F('M156.7 122.8L235.1 201.2C253.3 176.2 264 145.3 264 112C264 97.9 262.1 84.3 258.5 71.4C220.5 80.8 185.9 98.6 156.7 122.8zM122.8 156.7C98.6 185.9 80.8 220.5 71.4 258.5C84.3 262.1 97.9 264 112 264C145.3 264 176.1 253.3 201.2 235.1L122.8 156.7zM320 64C315.4 64 310.8 64.1 306.3 64.4C310 79.7 312 95.6 312 112C312 158.6 296.1 201.4 269.4 235.4L320 286.1L483.3 122.8C438.9 86.1 382.1 64 320 64zM112 312C95.6 312 79.6 310 64.4 306.3C64.2 310.8 64 315.4 64 320C64 382.1 86.1 438.9 122.8 483.3L286.1 320L235.4 269.4C201.4 296.1 158.6 312 112 312zM575.6 333.7C575.8 329.2 576 324.6 576 320C576 257.9 553.9 201.1 517.2 156.7L353.9 320L404.6 370.6C438.6 343.9 481.5 328 528 328C544.4 328 560.4 330 575.6 333.7zM568.5 381.5C555.6 377.9 542 376 527.9 376C494.6 376 463.8 386.7 438.7 404.9L517.1 483.3C541.3 454.1 559.1 419.5 568.5 381.5zM404.9 438.8C386.7 463.8 376 494.7 376 528C376 542.1 377.9 555.7 381.5 568.6C419.5 559.2 454.1 541.4 483.3 517.2L404.9 438.8zM370.6 404.5L320 353.9L156.7 517.2C201 553.9 257.9 576 320 576C324.6 576 329.2 575.9 333.7 575.6C330 560.3 328 544.4 328 528C328 481.4 343.9 438.6 370.6 404.6z', '64 64 512 512', 22, 22) },
      { n: 'Viajes y lugares', cat: 3, ...F('M199.2 181.4L173.1 256L466.9 256L440.8 181.4C436.3 168.6 424.2 160 410.6 160L229.4 160C215.8 160 203.7 168.6 199.2 181.4zM103.6 260.8L138.8 160.3C152.3 121.8 188.6 96 229.4 96L410.6 96C451.4 96 487.7 121.8 501.2 160.3L536.4 260.8C559.6 270.4 576 293.3 576 320L576 512C576 529.7 561.7 544 544 544L512 544C494.3 544 480 529.7 480 512L480 480L160 480L160 512C160 529.7 145.7 544 128 544L96 544C78.3 544 64 529.7 64 512L64 320C64 293.3 80.4 270.4 103.6 260.8zM192 368C192 350.3 177.7 336 160 336C142.3 336 128 350.3 128 368C128 385.7 142.3 400 160 400C177.7 400 192 385.7 192 368zM480 400C497.7 400 512 385.7 512 368C512 350.3 497.7 336 480 336C462.3 336 448 350.3 448 368C448 385.7 462.3 400 480 400z', '64 96 512 448', 22, 19.25) },
      { n: 'Objetos', cat: 5, ...F('M420.9 448C428.2 425.7 442.8 405.5 459.3 388.1C492 353.7 512 307.2 512 256C512 150 426 64 320 64C214 64 128 150 128 256C128 307.2 148 353.7 180.7 388.1C197.2 405.5 211.9 425.7 219.1 448L420.8 448zM416 496L224 496L224 512C224 556.2 259.8 592 304 592L336 592C380.2 592 416 556.2 416 512L416 496zM312 176C272.2 176 240 208.2 240 248C240 261.3 229.3 272 216 272C202.7 272 192 261.3 192 248C192 181.7 245.7 128 312 128C325.3 128 336 138.7 336 152C336 165.3 325.3 176 312 176z', '128 64 384 528', 16, 22) },
      { n: 'Símbolos', cat: 6, ...F('M238.9 336C249.6 336 259.6 341.3 265.5 350.2L277.3 368L304 368C330.5 368 352 389.5 352 416L352 528C352 554.5 330.5 576 304 576L112 576C85.5 576 64 554.5 64 528L64 416C64 389.5 85.5 368 112 368L138.7 368L150.5 350.2C156.4 341.3 166.4 336 177.1 336L238.8 336zM517.5 324C523.1 319.1 531.4 318.7 537.4 323.1C543.4 327.5 545.7 335.5 542.7 342.4L504.3 432L560 432C566.7 432 572.6 436.1 575 442.4C577.4 448.7 575.6 455.7 570.6 460.1L442.6 572.1C437 577 428.7 577.4 422.7 573C416.7 568.6 414.4 560.6 417.4 553.7L455.9 464L400.1 464C393.4 464 387.5 459.9 385.1 453.6C382.7 447.3 384.5 440.3 389.5 435.9L517.5 323.9zM208 424C181.5 424 160 445.5 160 472C160 498.5 181.5 520 208 520C234.5 520 256 498.5 256 472C256 445.5 234.5 424 208 424zM547.8 64.4C554.3 63.3 560.9 64.8 566.3 68.8C572.4 73.3 576 80.5 576 88L576 240L575.7 244.9C572.4 269.1 545.2 288 512 288C476.7 288 448 266.5 448 240C448 213.5 476.7 192 512 192C517.5 192 522.9 192.6 528 193.6L528 144.3L416 177.9L416 288.1L415.7 293C412.4 317.2 385.2 336.1 352 336.1C316.7 336.1 288 314.6 288 288.1C288 261.6 316.7 240.1 352 240.1C357.5 240.1 362.9 240.7 368 241.7L368 136C368 125.4 375 116 385.1 113L545.1 65L547.8 64.4zM252.9 64C290 64 320 94 320 131.1L320 137.2C320 193.3 244.8 249.3 209.7 272.5C198.9 279.6 185.1 279.6 174.3 272.5C139.2 249.4 64 193.3 64 137.2L64 131.1C64 94 94 64 131.1 64C152.2 64 172 73.9 184.7 90.8L192 100.6L199.3 90.8C212 73.9 231.8 64 252.9 64z', '64 64 512 512', 22, 22) },
      { n: 'Banderas', cat: 7, ...F('M160 96C160 78.3 145.7 64 128 64C110.3 64 96 78.3 96 96L96 544C96 561.7 110.3 576 128 576C145.7 576 160 561.7 160 544L160 422.4L222.7 403.6C264.6 391 309.8 394.9 348.9 414.5C391.6 435.9 441.4 438.5 486.1 421.7L523.2 407.8C535.7 403.1 544 391.2 544 377.8L544 130.1C544 107.1 519.8 92.1 499.2 102.4L487.4 108.3C442.5 130.8 389.6 130.8 344.6 108.3C308.2 90.1 266.3 86.5 227.4 98.2L160 118.4L160 96z', '96 64 448 512', 19.25, 22) }
    ];
    return this._emoIco;
  }

  // Índice emoji -> nombre, para los title="" y para validar lo que
  // viene de localStorage antes de inyectarlo como HTML.
  //
  // 23 nombres del catálogo vienen ya con entidades HTML dentro
  // («persona haciendo el gesto de &quot;no&quot;», los ideogramas
  // japoneses...). Si no se deshacen aquí, _emoEsc las escapa otra
  // vez y el tooltip enseña &quot; en crudo. Se decodifica una lista
  // cerrada y en una sola pasada; volver a escapar después es lo que
  // mantiene esto a salvo.
  _emoNombres() {
    if (this._emoNom) return this._emoNom;
    const ENT = { '&quot;': '"', '&amp;': '&', '&lt;': '<', '&gt;': '>', '&#39;': "'" };
    const limpio = (t) => String(t).replace(/&(?:quot|amp|lt|gt|#39);/g, (m) => ENT[m]);
    const m = Object.create(null);
    (window.RN_EMOJIS || []).forEach(c => c.e.forEach(e => { m[e[0]] = limpio(e[1]); }));
    this._emoNom = m;
    return m;
  }

  // Búsqueda sin tildes en los dos sentidos. El catálogo mezcla las
  // dos formas («árbol», «café», pero «mexico» sin acento), así que
  // plegar sólo la consulta no bastaría: se pliegan ambos lados. El
  // índice se cachea porque normalizar 1.898 cadenas en cada tecla
  // sería absurdo.
  _emoPlegar(t) {
    return String(t).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  }
  _emoIndiceBusqueda() {
    if (this._emoBus) return this._emoBus;
    const out = [];
    (window.RN_EMOJIS || []).forEach(c => c.e.forEach(e => out.push([e[0], this._emoPlegar(e[2])])));
    this._emoBus = out;
    return out;
  }

  _emoClaveFrec() { return 'rn_emojis_frecuentes_v1_' + ((this.USER && this.USER.id) || 0); }
  _emoCuenta() {
    try {
      const o = JSON.parse(localStorage.getItem(this._emoClaveFrec()) || '{}');
      return (o && typeof o === 'object') ? o : {};
    } catch (e) { return {}; }
  }
  // Los 16 más usados. El filtro contra el catálogo no es cosmético:
  // es lo que hace seguro inyectar esta lista como HTML, porque nada
  // que no sea un emoji ya presente en js/emojis.js llega a la rejilla.
  _emoFrecuentes() {
    const cuenta = this._emoCuenta();
    const nombres = this._emoNombres();
    const out = Object.keys(cuenta)
      .filter(e => nombres[e] !== undefined && cuenta[e] > 0)
      .sort((a, b) => cuenta[b] - cuenta[a] || a.localeCompare(b))
      .slice(0, 16);
    // Al estrenar la aplicación no hay historial; se completa con los
    // habituales para que la primera pestaña no salga vacía.
    for (const e of ['👍', '❤️', '🎉', '😂', '😍', '🔥', '👏', '🙌', '😮', '😢', '🤔', '✅', '🙏', '💯', '😅', '🥳']) {
      if (out.length >= 16) break;
      if (out.indexOf(e) < 0 && nombres[e] !== undefined) out.push(e);
    }
    return out;
  }
  _emoSumar(emoji) {
    try {
      const o = this._emoCuenta();
      o[emoji] = (o[emoji] || 0) + 1;
      localStorage.setItem(this._emoClaveFrec(), JSON.stringify(o));
    } catch (e) { /* modo privado o cuota llena: no pasa nada */ }
    this._emoHtml = null;   // la primera sección cambió
  }

  _emoEsc(t) {
    return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  _emoSeccion(i, titulo, lista, nombres) {
    const G = Component.EMO;
    const alto = G.CAB + Math.ceil(lista.length / G.COLS) * G.CELDA;
    const h = ['<section id="rnEmoSec' + i + '" style="content-visibility:auto;contain-intrinsic-size:auto ' +
               alto + 'px"><p>' + this._emoEsc(titulo) + '</p><div class="g">'];
    for (const e of lista) {
      h.push('<button type="button" data-e="' + this._emoEsc(e) + '" title="' +
             this._emoEsc(nombres[e] || '') + '">' + this._emoEsc(e) + '</button>');
    }
    h.push('</div></section>');
    return h.join('');
  }
  // Las nueve secciones seguidas, en HTML plano y cacheado. Pasarlas
  // por el sc-for costaría 201 ms en cada render de la aplicación
  // (medido: 1.898 emojis x el clon del objeto de ~291 claves que
  // walkFor hace por elemento), y aquí no dependen del estado.
  _emoHTMLTodo() {
    if (this._emoHtml) return this._emoHtml;
    const CAT = window.RN_EMOJIS || [];
    const nombres = this._emoNombres();
    const partes = [];
    this._emoIconos().forEach((ic, i) => {
      const lista = ic.cat < 0
        ? this._emoFrecuentes()
        : (CAT[ic.cat] ? CAT[ic.cat].e.map(e => e[0]) : []);
      partes.push(this._emoSeccion(i, ic.n, lista, nombres));
    });
    this._emoHtml = partes.join('');
    return this._emoHtml;
  }
  _emoHTMLBusqueda(q) {
    const out = [];
    for (const [emo, txt] of this._emoIndiceBusqueda()) {
      if (txt.indexOf(q) >= 0) { out.push(emo); if (out.length >= 180) break; }
    }
    if (!out.length) return '<p class="rn-emovacio">Ningún emoji coincide.</p>';
    return this._emoSeccion(0, 'Resultados', out, this._emoNombres());
  }

  // Rellena la rejilla e indexa los offsets de cada sección. Se llama
  // desde componentDidUpdate, así que tiene que ser idempotente: sólo
  // toca el DOM cuando cambia lo que hay que enseñar (si reinyectara
  // en cada render se perdería la posición del scroll).
  _emoMontar() {
    const g = document.getElementById('rnEmoGrid');
    if (!g) { this._emoOffs = null; this._emoIrA = null; return; }
    const q = this._emoPlegar((this.state.emoQ || '').trim());
    const clave = q ? 'q ' + q : 'cat';
    if (g.getAttribute('data-rn') !== clave) {
      g.innerHTML = q ? this._emoHTMLBusqueda(q) : this._emoHTMLTodo();
      g.setAttribute('data-rn', clave);
      g.scrollTop = 0;
      this._emoOffs = null;
    }
    if (!this._emoOffs) {
      this._emoOffs = Array.prototype.map.call(g.querySelectorAll('section'), s => s.offsetTop);
    }
    if (this._emoIrA != null) {
      const t = this._emoOffs[this._emoIrA];
      this._emoIrA = null;
      // Salto seco, no scroll suave: de una categoría a otra hay miles
      // de píxeles y animarlos desfilaría por todas las de en medio.
      // Además el scroll suave no se ejecuta con "reducir movimiento"
      // ni en una pestaña en segundo plano, y ahí el botón no haría
      // nada. Lo que sí se desliza es el subrayado, con su transition.
      if (t != null) g.scrollTop = t;
    }
  }
  _emoCerrar() { this.setState({ emoPicker: null, emoDi: null, emoQ: '' }); }
  _emoIr(i) {
    this._emoIrA = i;
    this.setState({ emoCat: i, emoQ: '' });
  }
  // Coloca el panel pegado al botón, sin salirse de la ventana.
  _emoAbrir(di, uid, btn) {
    const G = Component.EMO;
    const r = btn.getBoundingClientRect();
    const alto = Math.min(G.ALTO, window.innerHeight - 24);
    // En un teléfono de 375 px un panel de 400 dejaría fuera de la
    // pantalla la última columna de emojis, y con html,body en
    // overflow:hidden no habría manera de llegar hasta ella.
    const ancho = Math.min(G.ANCHO, window.innerWidth - 16);
    let x = r.left + r.width / 2 - ancho / 2;
    x = Math.max(8, Math.min(x, window.innerWidth - ancho - 8));
    let y = r.bottom + 8;
    if (y + alto > window.innerHeight - 8) y = r.top - 8 - alto;   // se abre hacia arriba
    // Y pase lo que pase, dentro de la ventana: si el botón queda a
    // medias del borde no se puede dejar el panel medio fuera.
    y = Math.max(8, Math.min(y, window.innerHeight - alto - 8));
    this.setState({
      emoPicker: uid, emoDi: di, emoQ: '', emoCat: 0,
      emoPos: { x: Math.round(x), y: Math.round(y), w: Math.round(ancho), h: alto }
    });
  }
  mutateItem(di, uid, fn) {
    const days = this.state.dayItems.map(a => a.map(x => ({ ...x })));
    const it = days[di].find(x => x.uid === uid);
    if (it) fn(it);
    this.setState({ dayItems: days });
  }
  money(v, dec) { return 'MX$' + v.toLocaleString('es-MX', { minimumFractionDigits: dec === undefined ? 2 : dec, maximumFractionDigits: dec === undefined ? 2 : dec }); }
  scrollContent(id) {
    const el = document.getElementById(id); const sc = document.getElementById(this.state.exOpen ? 'rnExScroll' : 'rnContentScroll');
    if (el && sc) {
      const off = el.getBoundingClientRect().top - sc.getBoundingClientRect().top;
      const target = sc.scrollTop + off - Math.max(24, sc.clientHeight / 2 - 90);
      sc.scrollTo({ top: Math.max(0, target), behavior: 'smooth' });
    }
  }
  openChatFull() {
    if (this.state.chatMode === 'full') return;
    this.setState({ chatMode: 'full', chatIn: false, chatAtRest: false });
    clearTimeout(this._cht); this._cht = setTimeout(() => this.tweenPanel('[data-screen-label="Asistente de IA — panel"]', true, () => this.setState({ chatIn: true, chatAtRest: true })), 30);
  }
  closeChatFull(target) {
    this.setState({ chatAtRest: false });
    clearTimeout(this._cht);
    this._cht = setTimeout(() => this.tweenPanel('[data-screen-label="Asistente de IA — panel"]', false, () => this.setState({ chatMode: target, chatIn: false })), 60);
  }
  tweenPanel(sel, toOpen, done) {
    clearInterval(this._chtI);
    const dur = 300; let t0 = null; let fired = false;
    const step = () => {
      const now = performance.now();
      if (t0 === null) t0 = now;
      const p = Math.min(1, (now - t0) / dur);
      const e = p < .5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
      const x = toOpen ? (e - 1) * 100 : -e * 100;
      const el = document.querySelector(sel);
      if (el) el.style.transform = 'translateX(' + x + '%)';
      if (p >= 1) { clearInterval(id); if (!fired) { fired = true; if (done) done(); } return; }
    };
    const id = setInterval(step, 16);
    this._chtI = id;
  }
  tweenEx(toOpen, done) {
    clearInterval(this._ext);
    const dur = 300; let t0 = null; let fired = false;
    const step = () => {
      const now = performance.now();
      if (t0 === null) t0 = now;
      const p = Math.min(1, (now - t0) / dur);
      const e = p < .5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
      const x = toOpen ? (e - 1) * 100 : -e * 100;
      const el = document.querySelector('[data-screen-label="Explorar Ensenada"]');
      if (el) el.style.transform = 'translateX(' + x + '%)';
      if (p >= 1) { clearInterval(id); if (!fired) { fired = true; if (done) done(); } return; }
    };
    const id = setInterval(step, 16);
    this._ext = id;
  }
  go(view, anchor) {
    const chatSt = this.state.chatMode === 'full' ? null : this.state.chatMode;
    if (this.state.chatMode === 'full') { clearInterval(this._chtI); }
    if (view === 'explorar') {
      const st = { exOpen: true, chatMode: chatSt };
      if (this.state.chatMode === 'full') { st.chatIn = false; st.chatAtRest = false; }
      if (!this.state.exOpen) {
        if (this.PLAN_ID && !this._placesLoaded) { st.skelPlaces = true; this._loadPlaces(); }
        else if (!this.PLAN_ID) { st.skelPlaces = true; setTimeout(() => this.setState({ skelPlaces: false }), 700); }
      }
      this.setState(st);
      clearTimeout(this._ex); this._ex = setTimeout(() => this.tweenEx(true, () => this.setState({ exIn: true, exAtRest: true })), 30);
      if (anchor) setTimeout(() => this.scrollContent(anchor), 380);
      else setTimeout(() => { const sc = document.getElementById('rnExScroll'); if (sc) sc.scrollTo({ top: 0 }); }, 40);
      return;
    }
    const st = { view: 'resumen', chatMode: chatSt };
    if (this.state.chatMode === 'full') { st.chatIn = false; st.chatAtRest = false; }
    if (this.state.exOpen) {
      st.exAtRest = false;
      clearTimeout(this._ex);
      this._ex = setTimeout(() => this.tweenEx(false, () => this.setState({ exOpen: false, exIn: false })), 60);
    }
    this.setState(st);
    if (anchor) setTimeout(() => this.scrollContent(anchor), 60);
  }
  // ════ Google Places (M3): detalle real con getDetails cacheado ════
  _details(pid, cb) {
    this._detCache = this._detCache || {};
    if (this._detCache[pid]) { cb(this._detCache[pid]); return; }
    if (!window.google || !google.maps || !google.maps.places) { cb(null); return; }
    const svc = new google.maps.places.PlacesService(this._map || document.createElement('div'));
    svc.getDetails({
      placeId: pid,
      fields: ['name', 'rating', 'user_ratings_total', 'formatted_address', 'formatted_phone_number',
        'website', 'opening_hours', 'photos', 'geometry', 'reviews', 'url',
        // Material del "Acerca de" de nivel 3. 'types' y 'business_status' son
        // de la capa básica; 'price_level' va en la misma capa que 'rating' y
        // 'reviews', que ya se piden, así que no sube de escalón de cobro.
        'types', 'price_level', 'business_status']
    }, (r, st) => {
      if (st !== 'OK' || !r) { console.warn('[plan] getDetails', st); cb(null); return; }
      const _loc = r.geometry && r.geometry.location;
      const d = {
        name: r.name || '',
        types: r.types || [],
        precio: (typeof r.price_level === 'number') ? r.price_level : null,
        estado: r.business_status || '',
        lat: _loc ? _loc.lat() : null,
        lng: _loc ? _loc.lng() : null,
        address: r.formatted_address || '',
        phone: r.formatted_phone_number || '',
        website: r.website || '',
        url: r.url || ('https://www.google.com/maps/place/?q=place_id:' + pid),
        weekday: (r.opening_hours && r.opening_hours.weekday_text) || [],
        fotos: (r.photos || []).slice(0, 3).map((f, i) => f.getUrl({ maxWidth: 500, maxHeight: i === 0 ? 320 : 720 })),
        reviews: (r.reviews || []).slice(0, 5).map(rv => ({
          head: (Number(rv.rating) || 0) + '/5 ' + (rv.author_name || 'Usuario de Google'),
          date: rv.relative_time_description || '',
          t: rv.text || '', stars: Number(rv.rating) || 0,
          who: rv.author_name || 'Usuario de Google'
        })),
        rating: Number(r.rating) || 0,
        rev: Number(r.user_ratings_total) || 0
      };
      this._detCache[pid] = d;
      cb(d);
    });
  }
  // ════ "Acerca de": cascada de tres niveles ════════════════════
  //  La librería legacy de Places NO tiene ningún campo de descripción
  //  (editorial_summary no existe en PlaceResult y no lo van a añadir),
  //  así que el texto se busca por descenso hasta un piso que siempre
  //  produce algo:
  //    1. editorialSummary, con la clase nueva Place  → museos, hoteles…
  //    2. Wikipedia verificada por coordenadas        → monumentos
  //    3. Plantilla armada con los datos estructurados → todo lo demás
  //  Nada de esto se guarda en la base de datos: los términos de Google
  //  sólo permiten persistir el place_id.
  _acercaDe(pid, d, cb) {
    if (d.acerca) { cb(d.acerca); return; }
    const fin = (res) => { d.acerca = res; cb(res); };
    this._acercaGoogle(pid, (g) => {
      if (g) { fin(g); return; }
      this._acercaWiki(d, (w) => {
        if (w) { fin(w); return; }
        fin({ texto: this._acercaPlantilla(d), fuente: 'plantilla' });
      });
    });
  }

  // ── Nivel 1: editorialSummary (Places API New) ──
  _acercaGoogle(pid, cb) {
    // Interruptor desde plan.php: el nivel 1 se cobra aparte y sólo trae
    // 1,000 llamadas gratis al mes, así que conviene poder apagarlo.
    if (!window.PLAN_ACERCA_GOOGLE) { cb(null); return; }
    if (!window.google || !google.maps || !google.maps.importLibrary) { cb(null); return; }
    let listo = false;
    const una = (v) => { if (!listo) { listo = true; cb(v); } };
    google.maps.importLibrary('places').then((lib) => {
      const Place = (lib && lib.Place) || (google.maps.places && google.maps.places.Place);
      if (!Place) { una(null); return; }
      // La clase nueva convive con PlacesService: no hay que migrar nada más.
      const p = new Place({ id: pid, requestedLanguage: 'es', requestedRegion: 'MX' });
      return p.fetchFields({ fields: ['editorialSummary', 'editorialSummaryLanguageCode'] })
        .then((res) => {
          const q = (res && res.place) || p;
          const txt = q.editorialSummary ? String(q.editorialSummary).trim() : '';
          const idi = String(q.editorialSummaryLanguageCode || '').slice(0, 2);
          // Google prohíbe alterar este texto, así que tampoco se puede
          // traducir: si no llega en español, se baja de nivel.
          if (!txt || (idi && idi !== 'es')) { una(null); return; }
          una({ texto: txt, fuente: 'google' });
        });
    }).catch((e) => {
      // Lo más probable: falta habilitar "Places API (New)" en Cloud.
      console.warn('[plan] editorialSummary no disponible:', e && e.message);
      una(null);
    });
  }

  // ── Nivel 2: Wikipedia, sólo para lugares que suelen tener artículo ──
  _acercaWiki(d, cb) {
    const CANDIDATOS = ['tourist_attraction', 'museum', 'art_gallery', 'church', 'place_of_worship',
      'park', 'natural_feature', 'zoo', 'aquarium', 'amusement_park', 'stadium', 'library', 'city_hall'];
    const T = d.types || [];
    // Un restaurante de barrio no tiene artículo: pedirlo sólo gasta tiempo
    // y aumenta el riesgo de traer el artículo equivocado.
    if (!T.some((t) => CANDIDATOS.indexOf(t) >= 0)) { cb(null); return; }
    if (d.lat == null || d.lng == null || !d.name) { cb(null); return; }
    fetch('api/lugar_wiki.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': this.CSRF },
      // La ciudad es la segunda prueba del lado del servidor cuando el
      // artículo no tiene coordenadas: sin ella no se acepta el match.
      body: JSON.stringify({ nombre: d.name, lat: d.lat, lng: d.lng, ciudad: (this.META && this.META.destino) || '' })
    })
      .then((r) => r.json())
      .then((j) => {
        if (j && j.ok && j.extracto) cb({ texto: j.extracto, fuente: 'wikipedia', titulo: j.titulo, url: j.url });
        else cb(null);
      })
      .catch(() => cb(null));
  }

  // ── Nivel 3: plantilla determinista (el piso, cubre el 100%) ──
  _acercaPlantilla(d) {
    const T = d.types || [];
    // Google suele poner el tipo principal primero; si no lo reconocemos,
    // se recorre la lista de lo específico a lo general. Sin esto, un
    // restaurante que además es 'bar' saldría descrito como bar.
    const NOMBRES = {
      museum: 'Museo', art_gallery: 'Galería de arte', aquarium: 'Acuario', zoo: 'Zoológico',
      amusement_park: 'Parque de diversiones', church: 'Iglesia', place_of_worship: 'Templo',
      stadium: 'Estadio', movie_theater: 'Cine', library: 'Biblioteca', city_hall: 'Edificio de gobierno',
      night_club: 'Club nocturno', bar: 'Bar', cafe: 'Cafetería', bakery: 'Panadería',
      meal_takeaway: 'Comida para llevar', meal_delivery: 'Comida a domicilio', restaurant: 'Restaurante',
      shopping_mall: 'Centro comercial', supermarket: 'Supermercado', store: 'Tienda',
      spa: 'Spa', gym: 'Gimnasio', campground: 'Zona para acampar', rv_park: 'Parque de casas rodantes',
      lodging: 'Hotel', park: 'Parque', natural_feature: 'Sitio natural', beach: 'Playa',
      tourist_attraction: 'Atracción turística', point_of_interest: 'Lugar de interés'
    };
    const ORDEN = ['museum', 'art_gallery', 'aquarium', 'zoo', 'amusement_park', 'church', 'place_of_worship',
      'stadium', 'movie_theater', 'library', 'city_hall', 'lodging', 'restaurant', 'cafe', 'bakery',
      'bar', 'night_club', 'meal_takeaway', 'meal_delivery', 'shopping_mall', 'supermarket', 'store',
      'spa', 'gym', 'campground', 'rv_park', 'beach', 'natural_feature', 'park',
      'tourist_attraction', 'point_of_interest'];

    let sust = NOMBRES[T[0]] || '';
    if (!sust) {
      for (let i = 0; i < ORDEN.length; i++) { if (T.indexOf(ORDEN[i]) >= 0) { sust = NOMBRES[ORDEN[i]]; break; } }
    }
    if (!sust) sust = 'Lugar';

    // Cocina, sólo si de verdad es un restaurante (evita "Cafetería japonesa")
    const COCINA = {
      seafood_restaurant: 'de mariscos', mexican_restaurant: 'de comida mexicana',
      italian_restaurant: 'italiano', japanese_restaurant: 'japonés', sushi_restaurant: 'de sushi',
      chinese_restaurant: 'chino', pizza_restaurant: 'de pizzas', steak_house: 'de cortes',
      vegetarian_restaurant: 'vegetariano', vegan_restaurant: 'vegano', seafood: 'de mariscos',
      hamburger_restaurant: 'de hamburguesas', taco_restaurant: 'de tacos', breakfast_restaurant: 'de desayunos'
    };
    let cocina = '';
    if (sust === 'Restaurante') {
      for (let i = 0; i < T.length; i++) { if (COCINA[T[i]]) { cocina = ' ' + COCINA[T[i]]; break; } }
    }

    // El adjetivo tiene que concordar: "Cafetería económica", no "económico".
    const FEMENINOS = ['Cafetería', 'Panadería', 'Galería de arte', 'Tienda', 'Playa', 'Iglesia',
      'Biblioteca', 'Zona para acampar', 'Atracción turística', 'Comida para llevar', 'Comida a domicilio'];
    const fem = FEMENINOS.indexOf(sust) >= 0;
    const PRECIOS = [
      fem ? 'económica' : 'económico',
      fem ? 'económica' : 'económico',
      'con precios moderados', 'con precios altos', 'de lujo'
    ];
    const precio = (d.precio != null && PRECIOS[d.precio]) ? ' ' + PRECIOS[d.precio] : '';

    // Zona a partir de la dirección: "Calle 1, Zona Centro, 22800 Ensenada, B.C."
    // Sólo se acepta si la forma es la esperada; ante la duda, se omite.
    let zona = '';
    const partes = String(d.address || '').split(',').map((x) => x.trim()).filter(Boolean);
    if (partes.length >= 3) {
      let ciudad = '';
      for (let i = 1; i < partes.length; i++) {
        const m = partes[i].match(/^\d{4,6}\s+(.+)$/);   // "22800 Ensenada"
        if (m) { ciudad = m[1].trim(); break; }
      }
      const col = partes[1];
      const colOk = col && !/\d/.test(col) && col !== ciudad && col.length <= 40;
      if (colOk && ciudad) zona = col + ', ' + ciudad;
      else if (ciudad) zona = ciudad;
      else if (colOk) zona = col;
    }
    // La coma sólo cabe si antes hubo un modificador: "Restaurante de mariscos
    // con precios moderados, en Zona Centro" sí, pero "Museo, en Centro" no.
    const lugarTxt = zona ? ((cocina || precio) ? ', en ' : ' en ') + zona : '';

    const frases = [];
    if (d.estado === 'CLOSED_PERMANENTLY') {
      frases.push('Google Maps lo marca como cerrado permanentemente.');
    } else if (d.estado === 'CLOSED_TEMPORARILY') {
      frases.push('Google Maps lo marca como cerrado temporalmente.');
    }
    frases.push(sust + cocina + precio + lugarTxt + '.');

    const rat = Number(d.rating) || 0;
    const n = Number(d.rev) || 0;
    if (rat > 0 && n > 0) {
      frases.push(rat.toFixed(1) + ' estrellas con ' + n.toLocaleString('es-MX') +
        (n === 1 ? ' opinión' : ' opiniones') + ' en Google Maps.');
    } else if (rat > 0) {
      frases.push(rat.toFixed(1) + ' estrellas en Google Maps.');
    }
    return frases.join(' ');
  }

  // 'desde' recuerda con qué lista se abrió la ficha: la del día del
  // itinerario o la de resultados. Un lugar puede estar en las dos, y
  // sin esto el paginador no sabría cuál recorrer.
  openDetail(id, tab, desde) {
    // En pantalla estrecha (≤1024) el panel de la ficha vive DENTRO
    // del sc-if de mapVisible, que está desmontado: el clic escribía
    // el estado y no se pintaba absolutamente nada. Abrir el modal del
    // mapa monta el panel, y de paso enseña el pin del lugar.
    const patch = { detail: id, detailFrom: desde || null, detailTab: tab || 'about', detailLoading: true, layersOpen: false, mapSearchOpen: false,
      rvwOpen: {}, rvwShown: 5, detHoursOpen: false };
    if (this.state.narrow && !this.state.mapModal) patch.mapModal = true;
    this.setState(patch);
    clearTimeout(this._dt);
    const p = this.place(id);
    if (this.PLAN_ID && p && p.gpid) {
      if (this._map && p.lat != null) this._map.panTo({ lat: p.lat, lng: p.lng });
      this._details(p.gpid, (d) => {
        // Las reseñas reales alimentan el carrusel de la fila. Se
        // guardan TODAS las que dio Google (_details ya recorta a 5),
        // no sólo la primera: antes las otras cuatro se tiraban y los
        // chevrones no tenían a dónde avanzar.
        if (d && d.reviews && d.reviews.length && !(p.rvws || []).length) {
          p.rvws = d.reviews.slice(0, Component.RVW_TOPE).map(r => {
            const t = r.t || '';
            return { t: t.slice(0, 220) + (t.length > 220 ? '…' : ''), stars: r.stars, who: r.who };
          });
        }
        if (this.state.detail === id) this.setState({ detailLoading: false });
        // El "Acerca de" va aparte: la ficha ya se puede leer mientras se
        // resuelve, y su nivel 1 es una petición extra que se cobra.
        if (d) {
          this._acercaDe(p.gpid, d, () => {
            if (this.state.detail === id) this.setState({ acercaN: (this.state.acercaN || 0) + 1 });
          });
        }
      });
    } else {
      this._dt = setTimeout(() => this.setState({ detailLoading: false }), 550);
    }
  }
  // ════════════ Explorar ════════════
  static get EX_INI() { return 4; }    // lugares al entrar
  static get EX_MAS() { return 10; }   // lugares tras "Mostrar más"
  static get RVW_TOPE() { return 5; }  // reseñas del carrusel
  static get EX_SECS() {
    return [
      { k: 'top',  t: 'Lugares principales a visitar', chip: 'Qué hacer',      icono: 'que-hacer' },
      { k: 'eat',  t: 'Mejores sitios para comer',     chip: 'Dónde comer',    icono: 'donde-comer' },
      { k: 'stay', t: 'Alojamientos más destacados',   chip: 'Dónde alojarse', icono: 'donde-alojarse' }
    ];
  }
  // Reseñas de un lugar para el carrusel. Acepta la forma vieja (rvw,
  // una sola) para que el modo demo siga enseñando la suya.
  _rvwLista(p) {
    if (Array.isArray(p.rvws) && p.rvws.length) return p.rvws.slice(0, Component.RVW_TOPE);
    return p.rvw ? [p.rvw] : [];
  }
  // Despliega o repliega una sección de Explorar. Al replegar desaparecen
  // seis tarjetas de golpe: si no se hiciera nada, el enlace saltaría muy
  // por encima del borde superior y el usuario perdería el sitio. Se deja
  // clavado donde estaba. Al desplegar no se toca el scroll, que para eso
  // lo que interesa es ver lo que acaba de aparecer.
  _exMasMenos(k) {
    const abierta = !!(this.state.exMas || {})[k];
    const sc = document.getElementById('rnExScroll');
    const btn = document.getElementById('exMas' + k);
    const antes = (abierta && sc && btn)
      ? btn.getBoundingClientRect().top - sc.getBoundingClientRect().top : null;
    this.setState({ exMas: { ...(this.state.exMas || {}), [k]: !abierta } });
    if (antes === null) return;
    requestAnimationFrame(() => {
      const b2 = document.getElementById('exMas' + k);
      if (!b2 || !sc) return;
      const ahora = b2.getBoundingClientRect().top - sc.getBoundingClientRect().top;
      sc.scrollTop += (ahora - antes);
    });
  }
  _rvwIr(id, i) {
    this.setState({ rvwIdx: { ...(this.state.rvwIdx || {}), [id]: Math.max(0, i) } });
  }

  // ════════════ Horario y coste de un lugar ════════════
  // Horas cada media hora, de 0:00 a 23:30.
  static get HORAS() {
    const out = [];
    for (let h = 0; h < 24; h++) { out.push(h + ':00'); out.push(h + ':30'); }
    return out;
  }
  // Divisas: las mismas de includes/currency.php, que es quien valida
  // en el servidor.
  static get MONEDAS() {
    return [
      { c: 'MXN', s: '$', n: 'Peso mexicano' }, { c: 'USD', s: '$', n: 'Dólar estadounidense' },
      { c: 'EUR', s: '€', n: 'Euro' }, { c: 'GBP', s: '£', n: 'Libra esterlina' },
      { c: 'CAD', s: 'C$', n: 'Dólar canadiense' }, { c: 'JPY', s: '¥', n: 'Yen japonés' },
      { c: 'BRL', s: 'R$', n: 'Real brasileño' }, { c: 'COP', s: '$', n: 'Peso colombiano' },
      { c: 'ARS', s: '$', n: 'Peso argentino' }
    ];
  }
  static get PALETA() {
    return ['#E14434', '#E8722B', '#F2C230', '#5DBB63',
            '#2BB3A3', '#2D7FF9', '#6F42C1', '#F59B7C',
            '#E8609B', '#E8365D', '#C64BD1', '#8B5CF6',
            '#2D9CDB', '#12A47A', '#41A24D', '#38BDF8'];
  }

  // Categorías del gasto, con los iconos de Font Awesome. El viewBox
  // va recortado al glifo y el eje mayor mide 20 px, igual que en las
  // pestañas del selector de emojis.
  _gcats() {
    if (this._gcatsCache) return this._gcatsCache;
    const F = (t, vb, w, h, d) => ({ v: t.toLowerCase(), t, vb, w: String(w), h: String(h), d });
    this._gcatsCache = [
      F('Actividad', '32 128 576 384', 20, 13.33, 'M96 128C60.7 128 32 156.7 32 192L32 256C32 264.8 39.4 271.7 47.7 274.6C66.5 281.1 80 299 80 320C80 341 66.5 358.9 47.7 365.4C39.4 368.3 32 375.2 32 384L32 448C32 483.3 60.7 512 96 512L544 512C579.3 512 608 483.3 608 448L608 384C608 375.2 600.6 368.3 592.3 365.4C573.5 358.9 560 341 560 320C560 299 573.5 281.1 592.3 274.6C600.6 271.7 608 264.8 608 256L608 192C608 156.7 579.3 128 544 128L96 128zM448 400L448 240L192 240L192 400L448 400zM144 224C144 206.3 158.3 192 176 192L464 192C481.7 192 496 206.3 496 224L496 416C496 433.7 481.7 448 464 448L176 448C158.3 448 144 433.7 144 416L144 224z'),
      F('Comida', '80 64 464 512', 18.13, 20, 'M127.9 78.4C127.1 70.2 120.2 64 112 64C103.8 64 96.9 70.2 96 78.3L81.9 213.7C80.6 219.7 80 225.8 80 231.9C80 277.8 115.1 315.5 160 319.6L160 544C160 561.7 174.3 576 192 576C209.7 576 224 561.7 224 544L224 319.6C268.9 315.5 304 277.8 304 231.9C304 225.8 303.4 219.7 302.1 213.7L287.9 78.3C287.1 70.2 280.2 64 272 64C263.8 64 256.9 70.2 256.1 78.4L242.5 213.9C241.9 219.6 237.1 224 231.4 224C225.6 224 220.8 219.6 220.2 213.8L207.9 78.6C207.2 70.3 200.3 64 192 64C183.7 64 176.8 70.3 176.1 78.6L163.8 213.8C163.3 219.6 158.4 224 152.6 224C146.8 224 142 219.6 141.5 213.9L127.9 78.4zM512 64C496 64 384 96 384 240L384 352C384 387.3 412.7 416 448 416L480 416L480 544C480 561.7 494.3 576 512 576C529.7 576 544 561.7 544 544L544 96C544 78.3 529.7 64 512 64z'),
      F('Bebidas', '160 64 320 544', 11.76, 20, 'M224 64C208.7 64 195.6 74.8 192.6 89.7L163.2 237C161.1 247.5 160 258.2 160 269L160 272C160 349.4 215 414 288 428.8L288 544L224 544C206.3 544 192 558.3 192 576C192 593.7 206.3 608 224 608L416 608C433.7 608 448 593.7 448 576C448 558.3 433.7 544 416 544L352 544L352 428.8C425 414 480 349.4 480 272L480 269C480 258.3 478.9 247.6 476.8 237L447.4 89.7C444.4 74.8 431.3 64 416 64L224 64zM225.9 249.6L250.2 128L389.8 128L414.1 249.6C415.4 256 416 262.5 416 269L416 272C416 325 373 368 320 368C267 368 224 325 224 272L224 269C224 262.5 224.6 256 225.9 249.6z'),
      F('Alojamiento', '32 96 576 448', 20, 15.56, 'M64 96C81.7 96 96 110.3 96 128L96 352L320 352L320 224C320 206.3 334.3 192 352 192L512 192C565 192 608 235 608 288L608 512C608 529.7 593.7 544 576 544C558.3 544 544 529.7 544 512L544 448L96 448L96 512C96 529.7 81.7 544 64 544C46.3 544 32 529.7 32 512L32 128C32 110.3 46.3 96 64 96zM144 256C144 220.7 172.7 192 208 192C243.3 192 272 220.7 272 256C272 291.3 243.3 320 208 320C172.7 320 144 291.3 144 256z'),
      F('Compras', '96 32 448 512', 17.5, 20, 'M256 144C256 108.7 284.7 80 320 80C355.3 80 384 108.7 384 144L384 192L256 192L256 144zM208 192L144 192C117.5 192 96 213.5 96 240L96 448C96 501 139 544 192 544L448 544C501 544 544 501 544 448L544 240C544 213.5 522.5 192 496 192L432 192L432 144C432 82.1 381.9 32 320 32C258.1 32 208 82.1 208 144L208 192zM232 240C245.3 240 256 250.7 256 264C256 277.3 245.3 288 232 288C218.7 288 208 277.3 208 264C208 250.7 218.7 240 232 240zM384 264C384 250.7 394.7 240 408 240C421.3 240 432 250.7 432 264C432 277.3 421.3 288 408 288C394.7 288 384 277.3 384 264z'),
      F('Supermercado', '0 48 569 528', 20, 18.56, 'M24 48C10.7 48 0 58.7 0 72C0 85.3 10.7 96 24 96L69.3 96C73.2 96 76.5 98.8 77.2 102.6L129.3 388.9C135.5 423.1 165.3 448 200.1 448L456 448C469.3 448 480 437.3 480 424C480 410.7 469.3 400 456 400L200.1 400C188.5 400 178.6 391.7 176.5 380.3L171.4 352L475 352C505.8 352 532.2 330.1 537.9 299.8L568.9 133.9C572.6 114.2 557.5 96 537.4 96L124.7 96L124.3 94C119.5 67.4 96.3 48 69.2 48L24 48zM208 576C234.5 576 256 554.5 256 528C256 501.5 234.5 480 208 480C181.5 480 160 501.5 160 528C160 554.5 181.5 576 208 576zM432 576C458.5 576 480 554.5 480 528C480 501.5 458.5 480 432 480C405.5 480 384 501.5 384 528C384 554.5 405.5 576 432 576z'),
      F('Coche', '64 96 512 448', 20, 17.5, 'M199.2 181.4L173.1 256L466.9 256L440.8 181.4C436.3 168.6 424.2 160 410.6 160L229.4 160C215.8 160 203.7 168.6 199.2 181.4zM103.6 260.8L138.8 160.3C152.3 121.8 188.6 96 229.4 96L410.6 96C451.4 96 487.7 121.8 501.2 160.3L536.4 260.8C559.6 270.4 576 293.3 576 320L576 512C576 529.7 561.7 544 544 544L512 544C494.3 544 480 529.7 480 512L480 480L160 480L160 512C160 529.7 145.7 544 128 544L96 544C78.3 544 64 529.7 64 512L64 320C64 293.3 80.4 270.4 103.6 260.8zM192 368C192 350.3 177.7 336 160 336C142.3 336 128 350.3 128 368C128 385.7 142.3 400 160 400C177.7 400 192 385.7 192 368zM480 400C497.7 400 512 385.7 512 368C512 350.3 497.7 336 480 336C462.3 336 448 350.3 448 368C448 385.7 462.3 400 480 400z'),
      F('Gasolina', '80 64 496 512', 19.38, 20, 'M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z'),
      F('Vuelo', '36 80 572 480', 20, 16.78, 'M552 264C582.9 264 608 289.1 608 320C608 350.9 582.9 376 552 376L424.7 376L265.5 549.6C259.4 556.2 250.9 560 241.9 560L198.2 560C187.3 560 179.6 549.3 183 538.9L237.3 376L137.6 376L84.8 442C81.8 445.8 77.2 448 72.3 448L52.5 448C42.1 448 34.5 438.2 37 428.1L64 320L37 211.9C34.4 201.8 42.1 192 52.5 192L72.3 192C77.2 192 81.8 194.2 84.8 198L137.6 264L237.3 264L183 101.1C179.6 90.7 187.3 80 198.2 80L241.9 80C250.9 80 259.4 83.8 265.5 90.4L424.7 264L552 264z'),
      F('Tren', '128 64 384 544', 14.12, 20, 'M128 160C128 107 171 64 224 64L416 64C469 64 512 107 512 160L512 416C512 456.1 487.4 490.5 452.5 504.8L506.4 568.5C515 578.6 513.7 593.8 503.6 602.3C493.5 610.8 478.3 609.6 469.8 599.5L395.8 512L244.5 512L170.5 599.5C161.9 609.6 146.8 610.9 136.7 602.3C126.6 593.7 125.3 578.6 133.9 568.5L187.8 504.8C152.6 490.5 128 456.1 128 416L128 160zM192 192L192 288C192 305.7 206.3 320 224 320L416 320C433.7 320 448 305.7 448 288L448 192C448 174.3 433.7 160 416 160L224 160C206.3 160 192 174.3 192 192zM320 448C337.7 448 352 433.7 352 416C352 398.3 337.7 384 320 384C302.3 384 288 398.3 288 416C288 433.7 302.3 448 320 448z'),
      F('Cultura', '64 64 512 512', 20, 20, 'M302.7 69.1C313.2 62.3 326.8 62.3 337.3 69.1L561.3 213.1C573.2 220.8 578.7 235.4 574.7 249C570.7 262.6 558.2 272 544 272L512 272L512 480L563.2 518.4C571.3 524.4 576 533.9 576 544C576 561.7 561.7 576 544 576L96 576C78.3 576 64 561.7 64 544C64 533.9 68.7 524.4 76.8 518.4L128 480L128 480L128 272L96 272C81.8 272 69.3 262.6 65.3 249C61.3 235.4 66.8 220.7 78.7 213.1L302.7 69.1zM400 272L400 480L464 480L464 272L400 272zM288 480L352 480L352 272L288 272L288 480zM176 272L176 480L240 480L240 272L176 272z'),
      F('Otro', '128 64 384 512', 15, 20, 'M439.4 96L448 96C483.3 96 512 124.7 512 160L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 160C128 124.7 156.7 96 192 96L200.6 96C211.6 76.9 232.3 64 256 64L384 64C407.7 64 428.4 76.9 439.4 96zM376 176C389.3 176 400 165.3 400 152C400 138.7 389.3 128 376 128L264 128C250.7 128 240 138.7 240 152C240 165.3 250.7 176 264 176L376 176zM320 312C336.1 312 349.2 325.1 349.2 341.2C349.2 349.9 346.1 355.1 342.3 358.9C337.8 363.3 331.6 366.4 325.5 368.4C310.6 373.4 296 387.7 296 407.9C296 421.2 306.7 431.9 320 431.9C331.5 431.9 341.2 423.8 343.5 412.9C362.7 405.8 397.2 386.6 397.2 341.1C397.2 298.5 362.6 263.9 320 263.9C277.4 263.9 242.8 298.5 242.8 341.1C242.8 354.4 253.5 365.1 266.8 365.1C280.1 365.1 290.8 354.4 290.8 341.1C290.8 325 303.9 311.9 320 311.9zM348 480C348 464.5 335.5 452 320 452C304.5 452 292 464.5 292 480C292 495.5 304.5 508 320 508C335.5 508 348 495.5 348 480z')
    ];
    return this._gcatsCache;
  }
  _itemPorUid(uid) {
    const dias = this.state.dayItems || [];
    for (let di = 0; di < dias.length; di++) {
      const it = (dias[di] || []).find(x => x.uid === uid);
      if (it) return { di, it };
    }
    return null;
  }
  _parcheaItem(uid, patch) {
    const dias = this.state.dayItems.map(a => a.map(x => ({ ...x })));
    for (const arr of dias) {
      const it = arr.find(x => x.uid === uid);
      if (it) { Object.assign(it, patch); break; }
    }
    this.setState({ dayItems: dias });
  }

  // ── Horario ──
  _horaAbrir(uid, btn) {
    const ref = this._itemPorUid(uid);
    if (!ref) return;
    const r = btn.getBoundingClientRect();
    const A = 312, H = 330;
    let x = Math.max(8, Math.min(r.left, window.innerWidth - A - 8));
    let y = r.bottom + 8;
    if (y + H > window.innerHeight - 8) y = r.top - 8 - H;
    y = Math.max(8, Math.min(y, window.innerHeight - H - 8));
    this.setState({
      horaMenu: { uid, x: Math.round(x), y: Math.round(y) },
      hIni: ref.it.hora || '', hFin: ref.it.horaFin || '', hCual: 'ini'
    });
  }
  _horaCerrar() { this.setState({ horaMenu: null }); }
  _horaElegir(v) {
    if (this.state.hCual === 'fin') this.setState({ hFin: v });
    // Elegir el inicio salta al final: es el orden natural y ahorra
    // un clic en el caso normal.
    else this.setState({ hIni: v, hCual: 'fin' });
  }
  _horaAplicar(ini, fin) {
    const m = this.state.horaMenu;
    if (!m) return;
    const ref = this._itemPorUid(m.uid);
    this._parcheaItem(m.uid, { hora: ini, horaFin: fin, horario: ini ? (fin ? ini + ' - ' + fin : ini) : '' });
    if (ref && ref.it.sid) this._sync('plan_items.php', { action: 'update', id: ref.it.sid, hora: ini, hora_fin: fin });
    this._horaCerrar();
  }

  // ── Gasto ──
  _gastoAbrir(uid) {
    const ref = this._itemPorUid(uid);
    if (!ref) return;
    const it = ref.it;
    // Se trabaja sobre un borrador: nada cambia hasta pulsar Guardar.
    const rep = {};
    (it.reparto || []).forEach(r => { rep[r.uid] = { monto: r.monto, color: r.color || '' }; });
    this.setState({
      gastoMenu: { uid },
      gMonto: it.costo > 0 ? String(it.costo) : '',
      gMoneda: it.moneda || 'MXN', gCat: it.gastoCat || '', gDesc: it.gastoDesc || '',
      gModo: it.gastoModo || 'no', gRep: rep,
      gMonOpen: false, gCatOpen: false, gModoOpen: false, gDonaOpen: true, gColorUid: null
    });
  }
  _gastoCerrar() { this.setState({ gastoMenu: null, gMonOpen: false, gCatOpen: false, gModoOpen: false, gColorUid: null }); }
  _gastoNum(v) { const n = parseFloat(String(v == null ? '' : v).replace(',', '.')); return isFinite(n) && n > 0 ? n : 0; }
  // Reparte a partes iguales dejando el sobrante en el primero: 100
  // entre 3 son 33.33 tres veces y falta un céntimo; si no se le
  // asigna a alguien, el total deja de cuadrar.
  _gastoEquitativo(uids, total) {
    const out = {};
    if (!uids.length) return out;
    const cent = Math.round(total * 100);
    const base = Math.floor(cent / uids.length);
    let resto = cent - base * uids.length;
    uids.forEach((u, i) => { out[u] = (base + (i < resto ? 1 : 0)) / 100; });
    return out;
  }
  _gastoRecalcula(rep, total) {
    const uids = Object.keys(rep).map(Number);
    const montos = this._gastoEquitativo(uids, total);
    const out = {};
    uids.forEach((u, i) => { out[u] = { monto: montos[u], color: (rep[u] && rep[u].color) || Component.PALETA[i % 16] }; });
    return out;
  }
  _gastoModoCambia(modo) {
    if (modo === 'no') { this.setState({ gModo: modo, gRep: {}, gModoOpen: false, gColorUid: null }); return; }
    const s = this.state;
    let rep = { ...s.gRep };
    if (modo === 'todos') {
      rep = {};
      this.MIEMBROS.forEach((m, i) => {
        const v = s.gRep[m.uid];
        rep[m.uid] = { monto: 0, color: (v && v.color) || Component.PALETA[i % 16] };
      });
    } else if (!Object.keys(rep).length) {
      const yo = Number(this.USER.id) || (this.MIEMBROS[0] || {}).uid;
      if (yo) rep[yo] = { monto: 0, color: Component.PALETA[0] };
    }
    this.setState({ gModo: modo, gRep: this._gastoRecalcula(rep, this._gastoNum(s.gMonto)), gModoOpen: false });
  }
  _gastoToggleMiembro(uid) {
    const rep = { ...this.state.gRep };
    if (rep[uid]) delete rep[uid];
    else rep[uid] = { monto: 0, color: Component.PALETA[Object.keys(rep).length % 16] };
    this.setState({ gRep: this._gastoRecalcula(rep, this._gastoNum(this.state.gMonto)), gColorUid: null });
  }
  _gastoGuardar() {
    const m = this.state.gastoMenu;
    if (!m) return;
    const s = this.state;
    const ref = this._itemPorUid(m.uid);
    const total = this._gastoNum(s.gMonto);
    const reparto = Object.keys(s.gRep).map(u => ({ usuario_id: Number(u), monto: s.gRep[u].monto, color: s.gRep[u].color }));
    this._parcheaItem(m.uid, {
      costo: total, moneda: s.gMoneda, gastoCat: s.gCat, gastoDesc: s.gDesc, gastoModo: s.gModo,
      reparto: reparto.map(r => ({ uid: r.usuario_id, monto: r.monto, color: r.color }))
    });
    if (ref && ref.it.sid) {
      this._sync('plan_items.php', {
        action: 'update', id: ref.it.sid, precio: total > 0 ? total : '', moneda: s.gMoneda,
        gasto_cat: s.gCat, gasto_desc: s.gDesc, gasto_modo: s.gModo, reparto: reparto
      });
    }
    this._gastoCerrar();
  }
  _gastoBorrar() {
    const m = this.state.gastoMenu;
    if (!m) return;
    const ref = this._itemPorUid(m.uid);
    this._parcheaItem(m.uid, { costo: 0, gastoCat: '', gastoDesc: '', gastoModo: 'no', reparto: [] });
    if (ref && ref.it.sid) {
      this._sync('plan_items.php', { action: 'update', id: ref.it.sid, precio: '', gasto_cat: '', gasto_desc: '', gasto_modo: 'no', reparto: [] });
    }
    this._gastoCerrar();
  }

  // ════════════ Añadir un lugar al plan ════════════
  // Abre el menú de días anclado al botón. Antes el botón añadía
  // directamente con dia = 0 ("guardado sin asignar"), que el
  // servidor acepta pero que ninguna pantalla enseña: el lugar se
  // guardaba de verdad y no aparecía en ninguna parte.
  _addAbrir(id, btn) {
    if (this.state.addMenu && this.state.addMenu.id === id) { this._addCerrar(); return; }
    const r = btn.getBoundingClientRect();
    const ANCHO = 200;
    const alto = 12 + this._addOpciones(id).length * 38;
    let x = Math.min(Math.max(8, r.right - ANCHO), window.innerWidth - ANCHO - 8);
    let y = r.bottom + 6;
    if (y + alto > window.innerHeight - 8) y = r.top - 6 - alto;
    y = Math.max(8, Math.min(y, window.innerHeight - alto - 8));
    this.setState({ addMenu: { id, x: Math.round(x), y: Math.round(y) } });
  }
  _addCerrar() { this.setState({ addMenu: null }); }
  // Un día del itinerario por fila, con su color. No se ofrece
  // "Lugares para visitar": es el dia = 0 que no tiene dónde verse.
  _addOpciones(id) {
    const diActual = this._addDiaDe(id);
    const opts = this.DAYS.map((d, i) => ({
      t: d.label, color: d.color, di: i, esDia: true, esQuitar: false,
      cls: diActual === i ? 'on' : ''
    }));
    if (diActual !== null) opts.push({ t: 'Quitar del plan', color: '', di: -1, esDia: false, esQuitar: true, cls: 'rn-addquitar' });
    return opts;
  }
  // En qué día está ya este lugar, o null. Se busca por place_id
  // porque es lo que comparten Explorar, la ficha del mapa y el item.
  _addDiaDe(id) {
    const dias = this.state.dayItems || [];
    for (let di = 0; di < dias.length; di++) {
      for (const it of (dias[di] || [])) { if (it.pid && it.pid === id) return di; }
    }
    return null;
  }
  _addElegir(id, di) {
    this._addCerrar();
    if (di < 0) { this.toggleAdd(id); return; }        // quitar
    const actual = this._addDiaDe(id);
    if (actual === di) return;                          // ya está ahí
    if (actual === null) { this.toggleAdd(id, di + 1); return; }
    // Ya estaba en otro día: MOVER, no volver a añadir. Sin esto se
    // creaba una fila nueva en plan_items y la vieja quedaba huérfana.
    const dias = this.state.dayItems.map(a => [...a]);
    const it = (dias[actual] || []).find(x => x.pid === id);
    if (!it) return;
    dias[actual] = dias[actual].filter(x => x !== it);
    dias[di] = [...dias[di], it];
    this.setState({ dayItems: dias, justAdded: id });
    clearTimeout(this._ja); this._ja = setTimeout(() => this.setState({ justAdded: null }), 900);
    if (it.sid) this._sync('plan_items.php', { action: 'move', id: it.sid, dia: di + 1, orden: dias[di].length - 1 });
    this._reproject();
  }
  toggleAdd(id, day) {
    const a = { ...this.state.added };
    if (a[id] && day === undefined) {
      // Quitar del plan: eliminar en servidor el item cuyo place se
      // agregó. Se busca primero en el itinerario porque _addedSid
      // puede haber quedado desfasado tras un borrado o un movimiento.
      let sid = null;
      for (const arr of (this.state.dayItems || [])) {
        for (const x of arr) { if (x.pid && x.pid === id && x.sid) { sid = x.sid; break; } }
        if (sid) break;
      }
      if (!sid) sid = this._addedSid ? this._addedSid[id] : null;
      if (sid) {
        this._sync('plan_items.php', { action: 'del', id: sid });
        const days = this.state.dayItems.map(arr => arr.filter(x => x.sid !== sid));
        delete this._addedSid[id];
        delete a[id];
        this.setState({ added: a, addMenu: null, dayItems: days });
        this._reproject();
        return;
      }
      delete a[id]; this.setState({ added: a, addMenu: null }); return;
    }
    a[id] = true;
    this.setState({ added: a, addMenu: null, justAdded: id });
    clearTimeout(this._ja); this._ja = setTimeout(() => this.setState({ justAdded: null }), 900);
    // Persistir: day = índice de día 1-based (0 = guardados sin asignar)
    const p = this.place(id);
    if (p && this.PLAN_ID) {
      const dia = (typeof day === 'number' && day > 0) ? day : 0;
      const catMap = { atr: 'hacer', com: 'rest', hot: 'hotel', sav: 'custom' };
      this._sync('plan_items.php', {
        action: 'add', dia: dia, nombre: p.name,
        categoria: catMap[p.cat] || 'custom',
        place_id: p.gpid || '', lat: p.lat !== undefined && p.lat !== null ? p.lat : '', lng: p.lng !== undefined && p.lng !== null ? p.lng : '',
        imagen_url: (p.foto && p.foto.indexOf('picsum') < 0) ? p.foto : ''
      }, (j) => {
        this._addedSid = this._addedSid || {};
        this._addedSid[id] = j.id;
        if (dia > 0) {
          // reflejar en el itinerario del día elegido (con coordenadas
          // para que el pin del día aparezca proyectado)
          const di = dia - 1;
          const days = this.state.dayItems.map(x => [...x]);
          if (days[di]) {
            days[di].push({
              uid: 'i' + j.id, sid: j.id, name: p.name, nota: '', horario: '', costo: 0,
              travel: null, reacts: [],
              pid: p.gpid || null,
              lat: p.lat !== undefined ? p.lat : null,
              lng: p.lng !== undefined ? p.lng : null,
              img: p.foto || null
            });
            this.setState({ dayItems: days });
          }
        }
        this._reproject();
      });
    }
  }
  sendChat(txt) {
    const t = (txt || this.state.chatInput).trim(); if (!t || this.state.streaming) return;
    const log = [...this.state.chatLog, { who: 'u', t }];
    this.setState({ chatLog: log, chatInput: '', streaming: true, streamTxt: '' });
    const stream = (reply) => {
      let i = 0;
      clearInterval(this._si);
      this._si = setInterval(() => {
        i += 3 + Math.floor(Math.random() * 5);
        if (i >= reply.src.length) {
          clearInterval(this._si);
          this.setState({ streaming: false, streamTxt: '', chatLog: [...this.state.chatLog, { who: 'a', rich: reply.rich, t: reply.src }] });
        } else { this.setState({ streamTxt: reply.src.slice(0, i) }); }
      }, 30);
    };
    if (this.PLAN_ID) {
      // Asistente real (api/plan_ai.php) con el mismo streaming visual;
      // si el servidor falla, cae al aiReply local del prototipo.
      fetch('api/plan_ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF': this.CSRF },
        body: JSON.stringify({ plan_id: this.PLAN_ID, mensaje: t })
      }).then(r => r.json()).then(j => {
        if (j.ok && j.respuesta) stream({ rich: 'itin', src: j.respuesta });
        else stream(this.aiReply(t));
      }).catch(() => stream(this.aiReply(t)));
    } else {
      stream(this.aiReply(t));
    }
  }
  aiReply(q) {
    const ql = q.toLowerCase();
    if (ql.includes('comer') || ql.includes('comida') || ql.includes('restaurante')) {
      return { rich: 'eat', src: 'Estos son los lugares más queridos para comer en Ensenada: [La Guerrerense] para tostadas de ceviche en la calle, [Manzanilla] para mariscos de mantel largo, el [Mercado Negro] para tacos de pescado junto a la lonja, y [Muelle 3] para un menú corto frente al puerto. Si buscas algo dulce, termina en [Cafe Tomas] del centro.' };
    }
    if (ql.includes('atraccion')) {
      return { rich: 'attr', src: 'Las atracciones imperdibles de Ensenada son [La Bufadora] (el géiser marino), el [Malecón de Ensenada] con la Ventana al Mar, el [Mercado Negro], la [Plaza Cívica de la Patria] y, a 30 minutos, las rutas del vino del [Valle de Guadalupe].' };
    }
    return { rich: 'itin', src: 'Claro – aquí tienes un itinerario de 2 días en Ensenada, pensado para combinar paseo, comida rica y vistas bonitas.\n\n**Día 1: Centro y malecón**\n• Mañana en [Ventana al mar] para caminar junto al mar y empezar tranquilo.\n• Sigue a [Plaza Cívica de la Patria] y [Museo de historia] para una dosis de centro histórico.\n• Almuerzo en [La Cevicheria Oyster Bar.] o [La Concheria].\n• Tarde de café en [Xcaanda Coffee Bar & Roasters] o [Barra D` Café].\n• Cena en [COMAL Restaurante] o [El Rey Sol Restaurant].\n\n**Día 2: Cultura y paseo relajado**\n• Desayuno en [Café con leche] o [Casa Marcelo].\n• Visita [Centro Social, Cívico y Cultural, Riviera de Ensenada] y, si te interesa, [Caracol Centro Científico y Cultural A.C.]\n• Almuerzo en [IL MaXimo] o [Calma].\n• Tarde en [Tara Garden] para vistas y fotos.\n• Cena más casual [HUNTER Café y Restaurante – Suc. Calle Primera] o [La Patrona Antojería].\n\nSi quieres, te lo puedo convertir en un itinerario **más romántico, más barato, familiar o foodie**, o incluso en un plan con horarios.' };
  }
  componentDidMount() {
    this._esc = (e) => {
      if (e.key !== 'Escape') return;
      const s = this.state;
      if (s.gastoMenu) {
        if (s.gMonOpen || s.gCatOpen || s.gModoOpen) { this.setState({ gMonOpen: false, gCatOpen: false, gModoOpen: false }); return; }
        if (s.gColorUid != null) { this.setState({ gColorUid: null }); return; }
        this._gastoCerrar(); return;
      }
      if (s.horaMenu) { this._horaCerrar(); return; }
      if (s.emoPicker) { this._emoCerrar(); return; }
      if (s.addMenu) { this._addCerrar(); return; }
      if (s.modoMenu) { this.setState({ modoMenu: null }); return; }
      if (s.newListMenu) { this.setState({ newListMenu: false }); return; }
      if (s.userMenu || s.catOpen || s.addMenu !== null || s.dayMenu !== null) { this.setState({ userMenu: false, catOpen: false, addMenu: null, dayMenu: null }); return; }
      if (s.catAllOpen) { this.setState({ catAllOpen: false }); return; }
      if (s.tplOpen) { this.setState({ tplOpen: null, tplSel: {}, tplExp: {} }); return; }
      if (s.heroMenuOpen) { this.setState({ heroMenuOpen: false }); return; }
      if (s.desglose) { this.setState({ desglose: false }); return; }
      if (s.layersOpen) { this.setState({ layersOpen: false }); return; }
      if (s.mapSearchOpen) { this.setState({ mapSearchOpen: false }); return; }
      if (s.detail) { this.setState({ detail: null }); return; }
      if (s.chatMode === 'small') { this.setState({ chatMode: null }); return; }
      if (s.chatMode === 'full') { this.closeChatFull(null); return; }
      if (s.mapModal) { this.setState({ mapModal: false }); }
    };
    window.addEventListener('keydown', this._esc);
    this._outside = (e) => {
      if (!this.state.catAllOpen) return;
      const panel = document.getElementById('rnCatPanel');
      if (panel && !panel.contains(e.target)) this.setState({ catAllOpen: false });
    };
    document.addEventListener('mousedown', this._outside);
    this._onResize = () => {
      const w = window.innerWidth;
      const patch = { winW: w, narrow: w <= 1024, mobile: w <= 640 };
      if (patch.narrow !== this.state.narrow || patch.mobile !== this.state.mobile || w !== this.state.winW) this.setState(patch);
    };
    window.addEventListener('resize', this._onResize);
    this._onResize();
    if (window.gmapsReady) window.gmapsReady.then(() => this._initMap());
    this.setState({ skelGuides: true });
    setTimeout(() => this.setState({ skelGuides: false }), 700);
    // Descripción real del destino (Wikipedia es) cuando no hay una sembrada
    if (this.META.destino && !this.state.destinoDesc) {
      fetch('https://es.wikipedia.org/w/api.php?action=query&list=search&format=json&origin=*&srlimit=1&srsearch=' +
        encodeURIComponent(this.META.destino + ' ciudad'))
        .then(r => r.ok ? r.json() : null)
        .then(j => {
          const titulo = j && j.query && j.query.search && j.query.search[0] ? j.query.search[0].title : this.META.destino;
          return fetch('https://es.wikipedia.org/api/rest_v1/page/summary/' + encodeURIComponent(titulo));
        })
        .then(r => r.ok ? r.json() : null)
        .then(j => {
          if (j && j.extract && j.type !== 'disambiguation') this.setState({ destinoDesc: j.extract });
        }).catch(() => {});
    }
  }
  componentWillUnmount() { window.removeEventListener('keydown', this._esc); window.removeEventListener('resize', this._onResize); document.removeEventListener('mousedown', this._outside); clearInterval(this._si); clearTimeout(this._sf); clearInterval(this._sr); clearInterval(this._ext); clearTimeout(this._ex); clearInterval(this._chtI); clearTimeout(this._cht); clearTimeout(this._exScrollT); }

  renderVals() {
    const s = this.state;
    const V = {};
    V.noop = (e) => { if (e && e.preventDefault) e.preventDefault(); };
    // addMenu queda FUERA: tiene velo propio en la raíz. Mientras
    // estuvo aquí, el velo global (z-index:450) se pintaba encima del
    // menú —atrapado en el contexto de apilado z-index:45 de Explorar—
    // y se comía el clic sobre el día.
    V.anyMenu = s.userMenu || s.catOpen || s.dayMenu !== null || s.listMenu !== null;
    V.closeMenus = () => this.setState({ userMenu: false, catOpen: false, dayMenu: null, listMenu: null });
    // ── Horario del lugar ──
    V.horaOn = !!s.horaMenu;
    V.horaX = (s.horaMenu ? s.horaMenu.x : 0) + 'px';
    V.horaY = (s.horaMenu ? s.horaMenu.y : 0) + 'px';
    V.horaClose = () => this._horaCerrar();
    V.horaIniTxt = s.hIni || 'Elegir';
    V.horaFinTxt = s.hFin || 'Elegir';
    V.horaIniCol = s.hIni ? '#212529' : '#8b969d';
    V.horaFinCol = s.hFin ? '#212529' : '#8b969d';
    V.horaIniBg = s.hCual === 'ini' ? '#E9EFF6' : '#F1F3F5';
    V.horaFinBg = s.hCual === 'fin' ? '#E9EFF6' : '#F1F3F5';
    V.horaPickIni = () => this.setState({ hCual: 'ini' });
    V.horaPickFin = () => this.setState({ hCual: 'fin' });
    const horaSel = s.hCual === 'fin' ? s.hFin : s.hIni;
    V.horaOpts = Component.HORAS.map(h => ({
      v: h, t: h, cls: h === horaSel ? 'on' : '', pick: () => this._horaElegir(h)
    }));
    V.horaBorrar = () => this._horaAplicar('', '');
    V.horaGuardar = () => this._horaAplicar(s.hIni || '', s.hIni ? (s.hFin || '') : '');

    // ── Gasto del lugar ──
    V.gastoOn = !!s.gastoMenu;
    V.gastoClose = () => this._gastoCerrar();
    const mon = Component.MONEDAS.find(m => m.c === s.gMoneda) || Component.MONEDAS[0];
    V.gastoMonSim = mon.s;
    V.gastoMonCod = mon.c;
    V.gastoMonOpen = !!s.gMonOpen;
    V.gastoMonToggle = () => this.setState({ gMonOpen: !s.gMonOpen, gCatOpen: false, gModoOpen: false });
    V.gastoMonedas = Component.MONEDAS.map(m => ({
      sim: m.s, t: m.c + ' · ' + m.n, cls: m.c === s.gMoneda ? 'on' : '',
      pick: () => this.setState({ gMoneda: m.c, gMonOpen: false })
    }));
    V.gastoMonto = s.gMonto;
    V.gastoMontoChange = (e) => {
      const v = e.target.value;
      // Reparte el importe nuevo entre quienes ya estaban marcados.
      this.setState({ gMonto: v, gRep: this._gastoRecalcula(this.state.gRep, this._gastoNum(v)) });
    };
    const cats = this._gcats();
    const catSel = cats.find(c => c.v === s.gCat);
    const catIco = catSel || cats[cats.length - 1];
    V.gastoCatTxt = catSel ? catSel.t : 'Categoría';
    V.gastoCatCol = catSel ? '#212529' : '#8b969d';
    V.gastoCatD = catIco.d; V.gastoCatVB = catIco.vb; V.gastoCatW = catIco.w; V.gastoCatH = catIco.h;
    V.gastoCatOpen = !!s.gCatOpen;
    V.gastoCatToggle = () => this.setState({ gCatOpen: !s.gCatOpen, gMonOpen: false, gModoOpen: false });
    V.gastoCats = cats.map(c => ({
      t: c.t, d: c.d, vb: c.vb, w: c.w, h: c.h, cls: c.v === s.gCat ? 'on' : '',
      pick: () => this.setState({ gCat: c.v, gCatOpen: false })
    }));
    V.gastoDesc = s.gDesc;
    V.gastoDescChange = (e) => this.setState({ gDesc: e.target.value });

    const MODOS = [{ v: 'no', t: 'No dividir' }, { v: 'todos', t: 'Todos' }, { v: 'individuos', t: 'Individuos' }];
    V.gastoModoTxt = (MODOS.find(x => x.v === s.gModo) || MODOS[0]).t;
    V.gastoModoOpen = !!s.gModoOpen;
    V.gastoModoToggle = () => this.setState({ gModoOpen: !s.gModoOpen, gMonOpen: false, gCatOpen: false });
    V.gastoModos = MODOS.map(x => ({ t: x.t, cls: x.v === s.gModo ? 'on' : '', pick: () => this._gastoModoCambia(x.v) }));

    const fmt = (n) => n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    // "No dividir" sólo enseña de quién es el gasto, sin casillas ni
    // importes: no hay nada que repartir.
    const soloYo = s.gModo === 'no';
    const miembros = soloYo
      ? this.MIEMBROS.filter(m => m.uid === Number(this.USER.id)).concat(this.MIEMBROS.length ? [] : [])
      : this.MIEMBROS;
    const listaFilas = (soloYo && !miembros.length) ? this.MIEMBROS.slice(0, 1) : miembros;
    V.gastoFilas = listaFilas.map(m => {
      const on = !!s.gRep[m.uid];
      return {
        nombre: m.nombre, inicial: m.inicial,
        editable: !soloYo, conImporte: !soloYo,
        on: on, boxBd: on ? '#E7AD00' : '#ADB5BD', boxBg: on ? '#E7AD00' : '#ffffff',
        col: (soloYo || on) ? '#212529' : '#8b969d',
        inBd: on ? '#2266ED' : '#DEE2E6',
        monto: on ? String(s.gRep[m.uid].monto) : '0',
        toggle: () => this._gastoToggleMiembro(m.uid),
        montoChange: (e) => {
          const rep = { ...this.state.gRep };
          if (!rep[m.uid]) return;
          rep[m.uid] = { ...rep[m.uid], monto: this._gastoNum(e.target.value) };
          this.setState({ gRep: rep });
        },
        ok: V.noop
      };
    });
    const sumaRep = Object.keys(s.gRep).reduce((a, u) => a + (s.gRep[u].monto || 0), 0);
    const totalGasto = this._gastoNum(s.gMonto);
    const cuadra = Math.abs(sumaRep - totalGasto) < 0.005;
    V.gastoHayTotal = !soloYo && Object.keys(s.gRep).length > 0;
    V.gastoTotalTxt = mon.c + ' ' + fmt(sumaRep);
    V.gastoTotalCol = cuadra ? '#0D1F27' : '#C0341D';
    V.gastoDescuadre = !cuadra;
    V.gastoDescuadreTxt = sumaRep > totalGasto
      ? 'Te pasas ' + fmt(sumaRep - totalGasto) + ' del importe.'
      : 'Faltan ' + fmt(totalGasto - sumaRep) + ' por repartir.';

    // ── Distribución (dona + paleta) ──
    V.gastoDonaOpen = !!s.gDonaOpen;
    V.gastoDonaRot = s.gDonaOpen ? '0deg' : '-90deg';
    V.gastoDonaToggle = () => this.setState({ gDonaOpen: !s.gDonaOpen, gColorUid: null });
    const conMonto = Object.keys(s.gRep).map(Number).filter(u => (s.gRep[u].monto || 0) > 0);
    const sumaDona = conMonto.reduce((a, u) => a + s.gRep[u].monto, 0);
    V.gastoDonaHay = conMonto.length > 0;
    V.gastoDonaVacia = conMonto.length === 0;
    const C = 2 * Math.PI * 44;      // circunferencia del radio 44 del SVG
    let acum = 0;
    V.gastoDona = conMonto.map(u => {
      const m = this.MIEMBROS.find(x => x.uid === u) || { nombre: '—' };
      const frac = sumaDona > 0 ? s.gRep[u].monto / sumaDona : 0;
      const largo = C * frac;
      const off = -acum;
      acum += largo;
      return {
        nombre: m.nombre, color: s.gRep[u].color || Component.PALETA[0],
        pct: '%' + Math.round(frac * 100),
        dash: largo.toFixed(2) + ' ' + (C - largo).toFixed(2),
        offset: off.toFixed(2),
        borde: s.gColorUid === u ? '#D9D9D9' : 'transparent',
        pickColor: () => this.setState({ gColorUid: s.gColorUid === u ? null : u })
      };
    });
    V.gastoColorOn = s.gColorUid !== null && s.gColorUid !== undefined;
    V.gastoPaleta = Component.PALETA.map(c => ({
      c: c,
      ring: (s.gColorUid != null && s.gRep[s.gColorUid] && s.gRep[s.gColorUid].color === c) ? '0 0 0 2px #ffffff, 0 0 0 4px #6F42C1' : 'none',
      pick: () => {
        const rep = { ...this.state.gRep };
        const u = this.state.gColorUid;
        if (rep[u]) rep[u] = { ...rep[u], color: c };
        this.setState({ gRep: rep, gColorUid: null });
      }
    }));
    V.gastoGuardar = () => this._gastoGuardar();
    V.gastoBorrar = () => this._gastoBorrar();
    V.gastoCur = cuadra || soloYo ? 'pointer' : 'not-allowed';
    V.gastoOp = cuadra || soloYo ? '1' : '.55';

    V.addOpen = !!s.addMenu;
    V.addX = (s.addMenu ? s.addMenu.x : 0) + 'px';
    V.addY = (s.addMenu ? s.addMenu.y : 0) + 'px';
    V.addClose = () => this._addCerrar();
    V.addOpts = s.addMenu
      ? this._addOpciones(s.addMenu.id).map(o => ({ ...o, pick: () => this._addElegir(s.addMenu.id, o.di) }))
      : [];
    V.userMenu = s.userMenu;
    V.toggleUserMenu = () => this.setState({ userMenu: !s.userMenu, catOpen: false });
    V.catOpen = s.catOpen; V.catLabel = s.catLabel; V.catRot = s.catOpen ? '180deg' : '0deg';
    V.toggleCat = () => this.setState({ catOpen: !s.catOpen, userMenu: false });
    V.catOpts = ['Ciudad', 'Hoteles', 'Restaurantes', 'Cosas que hacer'].map(t => ({ t, w: t === s.catLabel ? 600 : 400, pick: () => this.setState({ catLabel: t, catOpen: false }) }));
    V.showNav = s.winW >= 1230; V.showBrandTxt = s.winW >= 640;
    V.searchW = s.winW >= 1230 ? '345px' : '220px';
    V.showCtaTxt = s.winW >= 1000; V.ctaPad = s.winW >= 1000 ? '8px 16px' : '8px 11px';

    // ── Lateral ──
    V.sideExpanded = s.side === 'exp' && !s.mobile;
    V.sideRail = s.side === 'rail' && !s.mobile;
    V.sideShow = !s.mobile && !s.exOpen && s.chatMode !== 'full';
    V.sideW = s.sideWpx + 'px';
    V.sideOp = '1';
    const sideGo = (target) => {
      clearTimeout(this._sf); clearInterval(this._sr);
      const to = target === 'exp' ? 192 : 44;
      const aside0 = document.querySelector('aside[data-screen-label="Barra lateral"]');
      const wrap0 = aside0 ? aside0.querySelector(':scope > div') : null;
      if (wrap0) wrap0.style.opacity = '0';
      this._sf = setTimeout(() => {
        const aside = document.querySelector('aside[data-screen-label="Barra lateral"]');
        if (!aside) { this.setState({ side: target, sideWpx: to }); return; }
        const from = aside.getBoundingClientRect().width, dur = 280;
        let t0 = null; let fired = false;
        const step = () => {
          const now = performance.now();
          if (t0 === null) t0 = now;
          const p = Math.min(1, (now - t0) / dur);
          const e = p < .5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
          const w = from + (to - from) * e;
          const a = document.querySelector('aside[data-screen-label="Barra lateral"]');
          const b = a ? a.querySelector('[aria-label="Asistente de IA"]') : null;
          if (a) a.style.width = w + 'px';
          if (b) b.style.width = (w + 15) + 'px';
          if (p >= 1) { clearInterval(id); if (!fired) { fired = true; this.setState({ side: target, sideWpx: to }); } return; }
        };
        const id = setInterval(step, 16);
        this._sr = id;
      }, 160);
    };
    V.collapseSide = () => sideGo('rail');
    V.expandSide = () => sideGo('exp');
    V.aiClick = () => this.openChatFull();
    V.aiJust = s.side === 'exp' ? 'flex-start' : 'center';
    V.aiPad = s.side === 'exp' ? '0 14px' : '0';
    V.aiRad = s.side === 'exp' ? '0 24px 24px 0' : '0 20px 20px 0';
    V.aiW = (s.sideWpx + 15) + 'px';
    V.grpRes = s.grpRes; V.grpIt = s.grpIt; V.grpPres = s.grpPres;
    V.grpResRot = s.grpRes ? '0deg' : '-90deg'; V.grpItRot = s.grpIt ? '0deg' : '-90deg'; V.grpPresRot = s.grpPres ? '0deg' : '-90deg';
    V.grpResToggle = () => this.setState({ grpRes: !s.grpRes });
    V.grpItToggle = () => this.setState({ grpIt: !s.grpIt });
    V.grpPresToggle = () => this.setState({ grpPres: !s.grpPres });
    const active = (on) => ({ bg: on ? '#0E2A33' : 'transparent', col: on ? '#ffffff' : '#33454e', w: on ? 600 : 500 });
    const inMainDots = s.view === 'resumen' && !s.exOpen;
    V.navResumen = { ...active(s.view === 'resumen' && !s.exOpen), click: () => { this.go('resumen'); setTimeout(() => { const sc = document.getElementById('rnContentScroll'); if (sc) sc.scrollTo({ top: 0, behavior: 'smooth' }); }, 60); } };
    V.railListDots = [{ title: 'Explorar' }].concat(s.lists).map((L, i) => ({ t: L.title, bg: (inMainDots && s.scrollSec === 'resumen' && (s.scrollDot || 0) === i) ? '#212529' : '#E9ECEF' }));
    V.navExplorar = { ...active(s.exOpen), click: () => { this.go('resumen'); setTimeout(() => { const sc = document.getElementById('rnContentScroll'); const el = document.getElementById('secResumen'); if (sc && el) sc.scrollTo({ top: Math.max(0, sc.scrollTop + el.getBoundingClientRect().top - sc.getBoundingClientRect().top - 14), behavior: 'smooth' }); }, 60); } };
    V.navPres = { ...active(false), click: () => this.go('resumen', 'secPresupuesto') };
    const DLAB = this.DAYS.map(d => d.label);
    V.sideDays = DLAB.map((label, i) => ({ label, ...active(false), click: () => { this.setState({ dayOpen: { ...this.state.dayOpen, [i]: true } }); this.go('resumen', 'secDia' + i); } }));
    V.railDays = this.DAYS.map((d, i) => ({ num: d.num, mes: d.mes, full: DLAB[i], w: 600, col: (inMainDots && s.scrollSec === 'itinerario' && (s.scrollDay || 0) === i) ? '#212529' : '#ADB5BD', click: () => { this.setState({ dayOpen: { ...this.state.dayOpen, [i]: true } }); this.go('resumen', 'secDia' + i); } }));
    V.railCalClick = () => this.go('resumen', 'secItinerario');
    const railBtn = (on, offBg) => ({ bg: on ? '#212529' : offBg, col: on ? '#FFFFFF' : '#212529' });
    const inMain = s.view === 'resumen';
    const rb1 = railBtn(inMain && s.scrollSec === 'resumen', '#E9ECEF');
    V.railResBg = rb1.bg; V.railResCol = rb1.col;
    const rb2 = railBtn(inMain && s.scrollSec === 'itinerario', '#E9ECEF');
    V.railCalBg = rb2.bg; V.railCalCol = rb2.col;
    const rb3 = railBtn(inMain && s.scrollSec === 'presupuesto', '#E9ECEF');
    V.railPresBg = rb3.bg; V.railPresCol = rb3.col;
    V.onScroll = (e) => {
      const sc = e.target;
      const mid = sc.getBoundingClientRect().top + sc.clientHeight * .45;
      let cur = 'resumen';
      for (const id of ['secResumen', 'secItinerario', 'secPresupuesto']) {
        const el = document.getElementById(id);
        if (el && el.getBoundingClientRect().top <= mid) cur = id.slice(3).toLowerCase();
      }
      let dot = 0;
      this.state.lists.forEach((L, i) => { const el = document.getElementById('secL' + L.id); if (el && el.getBoundingClientRect().top <= mid) dot = i + 1; });
      let day = 0;
      for (let i = 0; i < this.state.dayItems.length; i++) { const el = document.getElementById('secDia' + i); if (el && el.getBoundingClientRect().top <= mid) day = i; }
      if (cur !== this.state.scrollSec || dot !== this.state.scrollDot || day !== this.state.scrollDay) this.setState({ scrollSec: cur, scrollDot: dot, scrollDay: day });
    };
    V.chatOpenFull = () => this.openChatFull();
    V.chatOpenSmall = () => this.setState({ chatMode: s.chatMode ? null : 'small' });

    // ── Layout ──
    V.contentW = 'auto';
    V.contentFlex = '1 1 0%';
    V.contentShrink = '1';
    V.mapVisible = !s.narrow || s.mapModal;
    V.mapPos = (s.narrow && s.mapModal) ? 'fixed' : 'sticky';
    V.mapInset = (s.narrow && s.mapModal) ? '58px 0 0 0' : '0 auto auto auto';
    V.mapH = (s.narrow && s.mapModal) ? 'auto' : 'calc(100vh - 58px)';
    V.ovLeft = '0px';
    V.ovW = s.narrow ? '100%' : '50%';
    V.mapZ = (s.narrow && s.mapModal) ? 700 : 10;
    V.mapFabShow = s.narrow && !s.mapModal;
    V.mapFabTxt = 'Ver mapa';
    V.mapFabClick = () => this.setState({ mapModal: true });

    // ── Vistas ──
    const chatResting = s.chatMode === 'full' && s.chatAtRest;
    V.vBody = true;
    V.vMain = s.view === 'resumen' && !chatResting && !(s.exOpen && s.exAtRest);
    V.vExplorar = s.exOpen && !chatResting;
    V.exSlide = s.exIn ? 'translateX(0)' : 'translateX(-100%)';
    V.chatSlide = s.chatIn ? 'translateX(0)' : 'translateX(-100%)';
    V.vChat = s.chatMode === 'full';
    V.goExplorar = () => this.go('explorar');
    V.goResumen = () => this.go('resumen');
    V.scrollTop = () => { const sc = document.getElementById(s.exOpen ? 'rnExScroll' : 'rnContentScroll'); if (sc) sc.scrollTo({ top: 0, behavior: 'smooth' }); };

    // ── Resumen ──
    V.skelGuides = s.skelGuides; V.skelGuidesOff = !s.skelGuides;
    V.guideCols = s.mobile ? '1' : '3';
    const _gseed = encodeURIComponent(this.META.destino.toLowerCase().replace(/\s+/g, '-'));
    const _gfoto = (sec, fb) => {
      const p = (this.PLACES || []).find(x => x.sec === sec && x.foto && x.foto.indexOf('picsum') < 0);
      return p ? p.foto : 'https://picsum.photos/seed/' + fb + '/440/300';
    };
    V.guideCards = [
      { seed: 'rn-' + _gseed + '-g1', foto: _gfoto('eat', 'rn-' + _gseed + '-g1'), title: 'Mejores restaurantes en ' + this.META.destino, click: () => this.go('explorar', 'secEat') },
      { seed: 'rn-' + _gseed + '-g2', foto: 'https://picsum.photos/seed/rn-' + _gseed + '-g2/440/300', title: 'Mejores Hoteles en ' + this.META.destino, click: () => this.go('explorar') },
      { seed: 'rn-' + _gseed + '-g3', foto: _gfoto('top', 'rn-' + _gseed + '-g3'), title: 'Atracciones populares en ' + this.META.destino, click: () => this.go('explorar') }
    ];

    // ── Explorar ──
    V.exQ = s.exQ;
    V.exQChange = (e) => this.setState({ exQ: e.target.value });
    V.exNoSearch = s.exQ.trim() === '';
    V.catCols = s.mobile ? '1' : '3';
    V.catCards = ['restaurantes|Restaurantes', 'atracciones|Atracciones', 'cafes|Cafés', 'comida-rapida|Comida rápida', 'desayuno|Desayuno y brunch', 'romanticos|Lugares románticos', 'familiares|Restaurantes familiares', 'barras|Barras', 'compras|Compras'].map(x => { const [slug, t] = x.split('|'); return { iconEl: React.createElement('img', { src: 'img/expl/' + slug + '.png', alt: '', style: { width: '19px', height: '19px', objectFit: 'contain', flexShrink: 0 } }), t }; });
    V.catAllOpen = s.catAllOpen;
    V.catAllToggle = (e) => { if (e && e.preventDefault) e.preventDefault(); this.setState({ catAllOpen: !this.state.catAllOpen, catQ: '' }); };
    V.catQ = s.catQ;
    V.catQChange = (e) => this.setState({ catQ: e.target.value });
    const cq = s.catQ.trim().toLowerCase();
    const catSecs = this.CATALL.map(sec => ({
      t: sec.t, id: sec.id,
      items: sec.items.filter(it => !cq || it[1].toLowerCase().includes(cq)).map(it => ({ iconEl: React.createElement('img', { src: 'img/cat/' + it[0] + '.png', alt: '', style: { width: '14px', height: '14px', objectFit: 'contain', flexShrink: 0 } }), t: it[1] }))
    })).filter(sec => sec.items.length > 0);
    V.catSections = catSecs;
    V.catEmpty = cq !== '' && catSecs.length === 0;
    const catGoSec = (id) => {
      const sc = document.getElementById('rnCatScroll'); const el = document.getElementById(id);
      if (sc && el) sc.scrollTo({ top: sc.scrollTop + el.getBoundingClientRect().top - sc.getBoundingClientRect().top - 6, behavior: 'smooth' });
    };
    V.catChips = [{ t: 'Comida', id: 'catSecComida' }, { t: 'Restaurantes', id: 'catSecComida' }, { t: 'Atracciones', id: 'catSecAtracciones' }].map(c => ({ t: c.t, go: () => catGoSec(c.id) }));
    V.skelPlaces = s.skelPlaces; V.skelPlacesOff = !s.skelPlaces;
    const FA_STAR = 'M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z';
    const starRow = (rating, w, h) => {
      const el = [];
      for (let k = 1; k <= 5; k++) {
        const fill = Math.max(0, Math.min(1, rating - (k - 1)));
        const star = (color) => React.createElement('svg', { viewBox: '0 0 640 640', width: w, height: h, preserveAspectRatio: 'none', style: { position: 'absolute', inset: 0 } }, React.createElement('path', { d: FA_STAR, fill: color }));
        const layers = [];
        if (fill >= 1) layers.push(star('#FBBC07'));
        else if (fill <= 0) layers.push(star('#DADCE0'));
        else {
          layers.push(star('#DADCE0'));
          layers.push(React.createElement('span', { style: { position: 'absolute', top: 0, left: 0, bottom: 0, width: (fill * 100) + '%', overflow: 'hidden' } }, star('#FBBC07')));
        }
        el.push(React.createElement('span', { key: k, style: { position: 'relative', display: 'inline-block', width: w + 'px', height: h + 'px' } }, layers));
      }
      return React.createElement('span', { style: { display: 'inline-flex', gap: '1px' } }, el);
    };
    this._starRow = starRow;
    const exq = s.exQ.trim().toLowerCase();
    const match = (p) => !exq || p.name.toLowerCase().includes(exq);
    // `n` es el número que va dentro del pin. Llega del sitio que pinta
    // la fila, no del lugar: así cuenta la POSICIÓN dentro de su sección
    // y siempre empieza en 1, también cuando el buscador ha filtrado.
    const rowVM = (p, n) => {
      const isAdded = !!s.added[p.id];
      const hovered = s.hoverPlace === p.id;
      const dollar = p.price ? { on: '$'.repeat(p.price), off: '$'.repeat(4 - p.price) } : null;
      return {
        num: String(n), name: p.name,
        // El pin conserva SIEMPRE el color de su categoría. Al pasar por
        // encima sólo crece y levanta más sombra: teñirlo de amarillo
        // hacía perder de vista a qué sección pertenece.
        pinFill: this.PIN[p.cat], pinK: hovered ? '1.15' : '1',
        pinShadow: hovered
          ? 'drop-shadow(0 5px 7px rgba(13,31,39,.45))'
          : 'drop-shadow(0 2px 3px rgba(13,31,39,.3))',
        rowBg: hovered ? '#f7fafc' : 'transparent',
        enter: () => this.setState({ hoverPlace: p.id }),
        leave: () => { if (this.state.hoverPlace === p.id) this.setState({ hoverPlace: null }); },
        openDet: () => this.openDetail(p.id),
        rating: (Number(p.rating) || 0).toFixed(1), rev: (Number(p.rev) || 0).toLocaleString('es-MX'),
        stars: starRow(Number(p.rating) || 0, 15, 14),
        chips: p.chips || [], more: String(p.more || 0), hasMore: (p.more || 0) > 0,
        hasPrice: !!dollar, priceOn: dollar ? dollar.on : '', priceOff: dollar ? dollar.off : '',
        desc: p.desc || '', hasDesc: !!(p.desc || '').trim(), seed: p.seed,
        foto: p.foto || ('https://picsum.photos/seed/' + p.seed + '/300/240'),
        ...(() => {
          // Carrusel: hasta 5 reseñas. Los chevrones desaparecen en los
          // extremos en vez de quedarse pulsables sin hacer nada.
          const lista = this._rvwLista(p);
          const n = lista.length;
          const i = Math.min(Math.max(0, (s.rvwIdx || {})[p.id] || 0), Math.max(0, n - 1));
          const r0 = lista[i];
          return {
            hasRvw: n > 0,
            rvwTxt: r0 ? '«' + r0.t + '»' : '',
            rvwStars: r0 ? starRow(r0.stars, 15, 14) : null,
            rvwWho: r0 ? r0.who : '',
            rvwHayPrev: i > 0,
            rvwHaySig: i < n - 1,
            rvwPrev: () => this._rvwIr(p.id, i - 1),
            rvwSig: () => this._rvwIr(p.id, i + 1)
          };
        })(),
        addBg: isAdded ? '#E9EFF6' : '#0E2A33',
        addCol: isAdded ? '#0D1F27' : '#ffffff',
        addDiv: isAdded ? '#c9d5dd' : 'rgba(255,255,255,.3)',
        addShadow: isAdded ? 'none' : '0 2px 8px rgba(14,42,51,.28)',
        addTxt: isAdded ? 'Añadido' : 'Añadir al plan de viaje',
        justAdded: s.justAdded === p.id, bookIcon: s.justAdded !== p.id,
        // Los dos abren el mismo menú: elegir día es el único camino.
        addClick: (e) => this._addAbrir(p.id, e.currentTarget),
        caretClick: (e) => this._addAbrir(p.id, e.currentTarget)
      };
    };
    // Tres secciones, mismo molde. Se enseñan 4 al entrar y 10 al
    // pulsar "Mostrar más", que ahora es UNO por sección y va al final,
    // no uno debajo de cada lugar.
    const sections = [];
    // Numeración de los pines: la posición dentro de su sección, de 1 en
    // adelante. Se guarda por id para que el pin del mapa lleve el mismo
    // número que la fila, sin depender de lo que trajera el cargador.
    const exNum = {};
    Component.EX_SECS.forEach(sd => {
      const todos = this.PLACES.filter(p => p.sec === sd.k && match(p));
      todos.forEach((p, i) => { exNum[p.id] = i + 1; });
      if (!todos.length) return;
      const abierta = !!(s.exMas || {})[sd.k];
      const tope = abierta ? Component.EX_MAS : Component.EX_INI;
      sections.push({
        id: 'exSec' + sd.k, title: sd.t,
        rows: todos.slice(0, tope).map((p, i) => rowVM(p, i + 1)),
        // Al buscar no tiene sentido paginar: ya está filtrado. Fuera de
        // eso el enlace NO desaparece al desplegar, se convierte en
        // "Mostrar menos": si no, no habría manera de volver a 4.
        hayMas: !exq && todos.length > Component.EX_INI,
        masId: 'exMas' + sd.k,
        masTxt: abierta ? 'Mostrar menos' : 'Mostrar más',
        masRot: abierta ? '180deg' : '0deg',
        mas: (e) => { if (e) e.preventDefault(); this._exMasMenos(sd.k); }
      });
    });
    // La línea gris sólo separa una sección de la siguiente; entre los
    // lugares de una misma sección no va ninguna.
    sections.forEach((sc, i) => { sc.hayLinea = i < sections.length - 1; });
    V.exSections = sections;
    // Los chips de debajo de la descripción saltan a su sección.
    const irASec = (k) => {
      const sc = document.getElementById('rnExScroll');
      const el = document.getElementById('exSec' + k);
      if (!sc || !el) return;
      const destino = sc.scrollTop + el.getBoundingClientRect().top - sc.getBoundingClientRect().top - 12;
      const desde = sc.scrollTop;
      sc.scrollTo({ top: destino, behavior: 'smooth' });
      // Red de seguridad: con "reducir movimiento" o en una pestaña en
      // segundo plano el scroll suave no se ejecuta y el chip parecería
      // no hacer nada. Sólo se coloca a mano si NO se movió ni un
      // píxel; si el usuario interrumpió desplazando, se le respeta.
      clearTimeout(this._exScrollT);
      this._exScrollT = setTimeout(() => {
        if (Math.abs(sc.scrollTop - desde) < 1 && Math.abs(destino - desde) > 1) sc.scrollTop = destino;
      }, 320);
    };
    V.exChips = Component.EX_SECS.map(sd => ({
      t: sd.chip, img: 'img/expl/' + sd.icono + '.png',
      go: () => irASec(sd.k)
    }));
    V.exEmpty = exq !== '' && sections.length === 0;

    // ── Listas ──
    const ckVals = (item, toggle, remove) => ({
      t: item.t, done: item.done,
      ring: item.done ? '#4A53CE' : '#ADB5BD',
      bg: item.done ? '#4A53CE' : '#ffffff',
      col: '#212529',
      toggle, remove
    });
    V.userLists = s.lists.map((L, li) => {
      const patch = (obj) => { const arr = this.state.lists.map(x => ({ ...x })); Object.assign(arr[li], obj); this.setState({ lists: arr }); };
      // Añadir un lugar (elegido del buscador o escrito) como artículo
      const agregarLugarLista = (nombre) => {
        const txt = '📍 ' + String(nombre).trim();
        const cur = this.state.lists[li];
        patch({ items: [...cur.items, { t: txt, done: false }], placeTxt: '' });
        this.setState({ acKey: null, acItems: [] });
        if (typeof L.id !== 'number') return;
        this._sync('plan_listas.php', { action: 'item_add', id: L.id, texto: txt }, (j) => {
          const arr = this.state.lists.map(x => ({ ...x, items: x.items.map(y => ({ ...y })) }));
          const it = arr[li].items.find(y => y.t === txt && !y.iid);
          if (it) it.iid = j.item_id;
          this.setState({ lists: arr });
        });
      };
      return {
        id: L.id,
        rot: L.open ? '0deg' : '-90deg', open: L.open,
        toggle: () => patch({ open: !this.state.lists[li].open }),
        title: L.title, titleChange: (e) => { patch({ title: e.target.value }); this._syncListaTitulo(L.id, e.target.value); },
        menuOpen: s.listMenu === L.id,
        menuToggle: () => this.setState({ listMenu: this.state.listMenu === L.id ? null : L.id }),
        del: () => { this.setState({ lists: this.state.lists.filter(x => x.id !== L.id), listMenu: null }); this._sync('plan_listas.php', { action: 'del', id: L.id }); },
        isNote: L.type === 'note', isCheck: L.type === 'check',
        text: L.text || '',
        cardTitle: L.cardTitle || '', cardTitleChange: (e) => { patch({ cardTitle: e.target.value }); this._syncListaTitulo(L.id, e.target.value); },
        openTpl: () => this.setState({ tplOpen: { li }, tplTab: 0 }),
        // ── Nota editable in situ (clic → cursor donde se hizo clic) ──
        noteEditing: s.noteEdit === L.id,
        noteIdle: s.noteEdit !== L.id,
        noteDomId: 'rnNote' + L.id,
        noteDisplay: (L.text || '').trim() ? L.text : 'Escribe una nota…',
        noteCol: (L.text || '').trim() ? '#33454e' : '#98a4ac',
        noteOpen: (e) => {
          let off = null;
          try {
            const r = document.caretRangeFromPoint
              ? document.caretRangeFromPoint(e.clientX, e.clientY)
              : (document.caretPositionFromPoint ? document.caretPositionFromPoint(e.clientX, e.clientY) : null);
            if (r) off = (r.startOffset !== undefined) ? r.startOffset : r.offset;
          } catch (err) {}
          if (!(L.text || '').trim()) off = 0;
          this.setState({ noteEdit: L.id, listMenu: null });
          setTimeout(() => this._focusNota(L.id, off), 0);
        },
        noteChange: (e) => {
          const el = e.target, v = el.value;
          patch({ text: v });
          el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px';
          this._syncNota(L.id, v);
        },
        noteKey: (e) => { if (e.key === 'Escape') e.target.blur(); },
        noteBlur: () => {
          if (this.state.noteEdit === L.id) this.setState({ noteEdit: null });
          clearTimeout(this._notaT);
          if (typeof L.id === 'number') {
            this._sync('plan_listas.php', { action: 'set_texto', id: L.id, texto: this.state.lists[li].text || '' });
          }
        },
        // ── Artículos con arrastre por el grip ──
        items: (L.items || []).map((c, ci) => {
          const dk = li + ':' + ci;
          const dragging = !!(s.ckDrag && s.ckDrag.li === li && s.ckDrag.ci === ci);
          return Object.assign(ckVals(c,
            () => { const cur = this.state.lists[li].items[ci]; patch({ items: this.state.lists[li].items.map((x, j) => j === ci ? { ...x, done: !x.done } : x) }); if (cur && cur.iid) this._sync('plan_listas.php', { action: 'item_toggle', item_id: cur.iid, hecho: !cur.done }); },
            () => { const cur = this.state.lists[li].items[ci]; patch({ items: this.state.lists[li].items.filter((x, j) => j !== ci) }); if (cur && cur.iid) this._sync('plan_listas.php', { action: 'item_del', item_id: cur.iid }); }), {
            op: dragging ? '.45' : '1',
            dropSh: (s.ckDragOver === dk && !dragging) ? 'inset 0 2px 0 #E7AD00' : 'none',
            dragStart: (e) => {
              this.setState({ ckDrag: { li, ci } });
              try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(ci)); } catch (err) {}
            },
            dragOver: (e) => {
              const d = this.state.ckDrag;
              if (!d || d.li !== li) return;
              e.preventDefault();
              try { e.dataTransfer.dropEffect = 'move'; } catch (err) {}
              if (this.state.ckDragOver !== dk) this.setState({ ckDragOver: dk });
            },
            drop: (e) => {
              const d = this.state.ckDrag;
              if (!d || d.li !== li) return;
              e.preventDefault();
              const arr = this.state.lists[li].items.slice();
              const moved = arr.splice(d.ci, 1)[0];
              if (!moved) { this.setState({ ckDrag: null, ckDragOver: null }); return; }
              const destino = d.ci < ci ? ci - 1 : ci;
              arr.splice(destino, 0, moved);
              patch({ items: arr });
              this.setState({ ckDrag: null, ckDragOver: null });
              if (moved.iid) this._sync('plan_listas.php', { action: 'item_move', item_id: moved.iid, orden: destino });
            },
            dragEnd: () => this.setState({ ckDrag: null, ckDragOver: null })
          });
        }),
        newDomId: 'rnNew' + L.id,
        newTxt: L.newTxt || '', newChange: (e) => patch({ newTxt: e.target.value }),
        newKey: (e) => { const cur = this.state.lists[li]; if (e.key === 'Enter' && (cur.newTxt || '').trim()) { const txt = cur.newTxt.trim(); patch({ items: [...cur.items, { t: txt, done: false }], newTxt: '' }); this._sync('plan_listas.php', { action: 'item_add', id: L.id, texto: txt }, (j) => { const arr = this.state.lists.map(x => ({ ...x, items: x.items.map(y => ({ ...y })) })); const it = arr[li].items.find(y => y.t === txt && !y.iid); if (it) it.iid = j.item_id; this.setState({ lists: arr }); }); } },
        clearDone: () => { patch({ items: this.state.lists[li].items.filter(x => !x.done) }); this._sync('plan_listas.php', { action: 'clear_done', id: L.id }); },
        // ── "Añadir lugar": al enfocarse se expande y oculta los 2 botones ──
        placeDomId: 'rnPlace' + L.id,
        placeFocus: () => this.setState({ placeFocusId: L.id }),
        placeBlur: () => { if (this.state.placeFocusId === L.id) this.setState({ placeFocusId: null }); },
        btnW: s.placeFocusId === L.id ? '0px' : '42px',
        btnML: s.placeFocusId === L.id ? '-9px' : '0px',
        btnOp: s.placeFocusId === L.id ? '0' : '1',
        addNote: () => this._addLista('nota'),
        addCheck: () => this._addLista('check'),
        placeTxt: L.placeTxt || '',
        placeChange: (e) => { patch({ placeTxt: e.target.value }); this._acBuscar('l:' + L.id, e.target.value); },
        placeKey: (e) => {
          if (e.key === 'Escape') { this.setState({ acKey: null, acItems: [] }); return; }
          const cur = this.state.lists[li];
          if (e.key !== 'Enter' || !(cur.placeTxt || '').trim()) return;
          const sug = (this.state.acKey === 'l:' + L.id) ? this.state.acItems : [];
          agregarLugarLista(sug.length ? sug[0].main : cur.placeTxt.trim());
        },
        acOpen: s.acKey === 'l:' + L.id && s.acItems.length > 0,
        acItems: (s.acKey === 'l:' + L.id ? s.acItems : []).map(it => ({
          main: it.main, sec: it.sec,
          pick: (e) => { if (e && e.preventDefault) e.preventDefault(); agregarLugarLista(it.main); }
        }))
      };
    });
    // ── "+ Nueva lista" → menú (lugar / nota / lista de verificación) ──
    V.newListMenu = s.newListMenu;
    V.newListToggle = (e) => { if (e && e.preventDefault) e.preventDefault(); this.setState({ newListMenu: !this.state.newListMenu }); };
    V.newListOpts = [
      {
        t: 'Añadir un lugar',
        d: 'M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z',
        pick: () => { this.setState({ newListMenu: false }); this._enfocarLugar(); }
      },
      {
        t: 'Añadir una nota',
        d: 'M336 496L160 496C151.2 496 144 488.8 144 480L144 160C144 151.2 151.2 144 160 144L480 144C488.8 144 496 151.2 496 160L496 336L408 336C368.2 336 336 368.2 336 408L336 496zM476.1 384L384 476.1L384 408C384 394.7 394.7 384 408 384L476.1 384zM96 480C96 515.3 124.7 544 160 544L357.5 544C374.5 544 390.8 537.3 402.8 525.3L525.3 402.7C537.3 390.7 544 374.4 544 357.4L544 160C544 124.7 515.3 96 480 96L160 96C124.7 96 96 124.7 96 160L96 480z',
        pick: () => { this.setState({ newListMenu: false }); this._addLista('nota'); }
      },
      {
        t: 'Añadir lista de verificación',
        d: 'M197.8 100.3C208.7 107.9 211.3 122.9 203.7 133.7L147.7 213.7C143.6 219.5 137.2 223.2 130.1 223.8C123 224.4 116 222 111 217L71 177C61.7 167.6 61.7 152.4 71 143C80.3 133.6 95.6 133.7 105 143L124.8 162.8L164.4 106.2C172 95.3 187 92.7 197.8 100.3zM197.8 260.3C208.7 267.9 211.3 282.9 203.7 293.7L147.7 373.7C143.6 379.5 137.2 383.2 130.1 383.8C123 384.4 116 382 111 377L71 337C61.6 327.6 61.6 312.4 71 303.1C80.4 293.8 95.6 293.7 104.9 303.1L124.7 322.9L164.3 266.3C171.9 255.4 186.9 252.8 197.7 260.4zM288 160C288 142.3 302.3 128 320 128L544 128C561.7 128 576 142.3 576 160C576 177.7 561.7 192 544 192L320 192C302.3 192 288 177.7 288 160zM288 320C288 302.3 302.3 288 320 288L544 288C561.7 288 576 302.3 576 320C576 337.7 561.7 352 544 352L320 352C302.3 352 288 337.7 288 320zM224 480C224 462.3 238.3 448 256 448L544 448C561.7 448 576 462.3 576 480C576 497.7 561.7 512 544 512L256 512C238.3 512 224 497.7 224 480zM128 440C150.1 440 168 457.9 168 480C168 502.1 150.1 520 128 520C105.9 520 88 502.1 88 480C88 457.9 105.9 440 128 440z',
        pick: () => { this.setState({ newListMenu: false }); this._addLista('check'); }
      }
    ];

    // ── Selector de emojis (catálogo Unicode completo, js/emojis.js) ──
    // La rejilla NO se pinta aquí: la rellena _emoMontar() con HTML
    // estático. Aquí sólo van las pestañas, el subrayado y el buscador.
    const G = Component.EMO;
    const emoBusca = (s.emoQ || '').trim().toLowerCase();
    const emoPos = s.emoPos || { x: 0, y: 0, w: G.ANCHO, h: G.ALTO };
    const emoW = emoPos.w || G.ANCHO;
    V.emoOpen = !!s.emoPicker;
    V.emoX = emoPos.x + 'px';
    V.emoY = emoPos.y + 'px';
    V.emoW = emoW + 'px';
    V.emoH = emoPos.h + 'px';
    V.emoQ = s.emoQ || '';
    V.emoQChange = (e) => {
      const v = e.target.value;
      // Al vaciar el buscador la rejilla vuelve arriba (_emoMontar),
      // así que la pestaña subrayada tiene que volver con ella; si no,
      // se queda señalando una categoría que ya no se está viendo.
      this.setState(v.trim() ? { emoQ: v } : { emoQ: v, emoCat: 0 });
    };
    V.emoClose = () => this._emoCerrar();
    const emoIdx = Math.min(Math.max(0, s.emoCat || 0), this._emoIconos().length - 1);
    V.emoTabs = this._emoIconos().map((ic, i) => ({
      n: ic.n, d: ic.d, vb: ic.vb, w: ic.w, h: ic.h,
      relleno: ic.relleno, trazo: ic.trazo, grosor: ic.grosor,
      cls: (!emoBusca && i === emoIdx) ? 'on' : '',
      pick: () => this._emoIr(i)
    }));
    // Buscando no hay secciones que subrayar, así que se desvanece.
    V.emoUnderOp = emoBusca ? '0' : '1';
    const tabW = emoW / this._emoIconos().length;
    V.emoUnderX = (emoIdx * tabW + (tabW - G.SUB) / 2).toFixed(1) + 'px';
    V.emoPick = (e) => {
      const b = e.target && e.target.closest ? e.target.closest('button[data-e]') : null;
      if (!b) return;
      const emoji = b.getAttribute('data-e');
      const di = this.state.emoDi, uid = this.state.emoPicker;
      if (!emoji || uid == null || di == null) return;
      // El lugar puede haber desaparecido con el panel abierto (lo
      // borró otra persona del plan, o el propio usuario). reaccionar()
      // se limita a volver sin avisar, así que sin esta comprobación
      // la reacción se perdía en silencio y encima contaba como usada.
      const fila = (this.state.dayItems || [])[di];
      if (!fila || !fila.some(x => x.uid === uid)) { this._emoCerrar(); return; }
      this._emoSumar(emoji);
      this.reaccionar(di, uid, emoji);
    };
    V.emoScroll = (e) => {
      const g = e.target;
      const offs = this._emoOffs;
      if (!offs || !offs.length || emoBusca) return;
      let a = 0;
      for (let i = 0; i < offs.length; i++) if (offs[i] <= g.scrollTop + 6) a = i;
      // Sin esto, una última sección más corta que el panel nunca
      // llegaría a activarse por mucho que se baje.
      if (g.scrollTop + g.clientHeight >= g.scrollHeight - 2) a = offs.length - 1;
      if (a !== this.state.emoCat) this.setState({ emoCat: a });
    };

    // ── Itinerario ──
    const DAYMETA = this.DAYS.map(d => ({ title: d.title, total: '', color: d.color }));
    // Recomendados por día: sobrantes del textSearch de Explorar
    // (con servidor); demo de La Paz solo en modo sin servidor
    const RECS = this.PLAN_ID
      ? (this.RECS_REAL || []).slice(0, 3)
      : [
        { seed: 'rn-lp-serpentario', name: 'Serpentario de La Paz', foto: 'https://picsum.photos/seed/rn-lp-serpentario/96/96' },
        { seed: 'rn-lp-artesano', name: 'Casa del Artesano Sudcaliforniano', foto: 'https://picsum.photos/seed/rn-lp-artesano/96/96' },
        { seed: 'rn-lp-marina', name: 'Marina Cortez', foto: 'https://picsum.photos/seed/rn-lp-marina/96/96' }
      ];
    const moneyChip = (n) => 'MX$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const mkGap = (di, i) => {
      const gk = di + ':' + i; const over = s.dragOver === gk;
      return {
        gapH: s.drag ? (over ? '42px' : '12px') : '5px',
        gapB: over ? '2px dashed #d49e1f' : '2px dashed transparent',
        gapBg: over ? 'rgba(245,185,63,.14)' : 'transparent',
        gapOver: (e) => { e.preventDefault(); if (this.state.dragOver !== gk) this.setState({ dragOver: gk }); },
        gapLeave: () => { if (this.state.dragOver === gk) this.setState({ dragOver: null }); },
        gapDrop: (e) => {
          e.preventDefault(); const d = this.state.drag; if (!d) return;
          const days = this.state.dayItems.map(a => [...a]);
          const idx = days[d.day].findIndex(x => x.uid === d.uid);
          if (idx < 0) { this.setState({ drag: null, dragOver: null }); return; }
          const it = days[d.day].splice(idx, 1)[0];
          let ti = i; if (d.day === di && idx < ti) ti -= 1;
          const orden = Math.min(ti, days[di].length);
          days[di].splice(orden, 0, it);
          this.setState({ dayItems: days, drag: null, dragOver: null });
          if (it.sid) this._sync('plan_items.php', { action: 'move', id: it.sid, dia: di + 1, orden: orden });
        }
      };
    };
    const addToDay = (di, name, extra) => this._addItemDia(di, name, extra);
    V.planDays = DAYMETA.map((meta, di) => {
      const items = s.dayItems[di];
      return {
        secId: 'secDia' + di, title: meta.title, total: meta.total,
        open: !!s.dayOpen[di], rot: s.dayOpen[di] ? '90deg' : '0deg',
        toggle: () => this.setState({ dayOpen: { ...this.state.dayOpen, [di]: !this.state.dayOpen[di] } }),
        menuOpen: s.dayMenu === di,
        menuToggle: () => this.setState({ dayMenu: this.state.dayMenu === di ? null : di }),
        menuClose: () => this.setState({ dayMenu: null }),
        sub: s.daySubs[di],
        subChange: (e) => { const arr = [...this.state.daySubs]; arr[di] = e.target.value; this.setState({ daySubs: arr }); this._syncSubs(); },
        empty: items.length === 0, hasItems: items.length > 0,
        items: items.map((it, i) => ({
          ...mkGap(di, i),
          num: String(i + 1), name: it.name,
          pinFill: meta.color,
          // El nombre abre la ficha del lugar en el mapa
          nameTitle: 'Ver información de ' + it.name,
          // Al pulsar la tarjeta se despliegan "Añadir" (hora) y
          // "Añadir costo". Se abre una sola a la vez.
          abierto: s.itemOpen === it.uid,
          sinNota: !(it.nota || '').trim(),
          toggleAbierto: (e) => {
            // Los controles de dentro (botones, asa de arrastre) ya
            // tienen su acción; el resto de la tarjeta despliega.
            //
            // El nombre YA NO se descarta: su <p> tenía flex:1, o sea
            // que su caja ocupaba el 87 % del ancho aunque el texto
            // fuera corto, y ahí el clic no hacía nada. Ahora el <p>
            // se ciñe al texto y, si se pulsa, abre la ficha del lugar
            // Y despliega la tarjeta: las dos cosas son coherentes.
            const t = e && e.target;
            if (t && t.closest && t.closest('button, input, textarea, select, a, .rn-grip')) return;
            this.setState({ itemOpen: this.state.itemOpen === it.uid ? null : it.uid });
          },
          addHora: (e) => this._horaAbrir(it.uid, e.currentTarget),
          addCosto: () => this._gastoAbrir(it.uid),
          openDet: () => { this._centrarEn(it); this.openDetail(it.pid || it.uid, null, 'itin'); },
          hasNota: !!it.nota, nota: it.nota,
          hasChips: !!(it.horario || it.costo > 0), hasHorario: !!it.horario, horario: it.horario,
          hasCosto: it.costo > 0, costo: it.costo > 0 ? moneyChip(it.costo) : '',
          ...(() => {
            // Traslado de ESTE lugar al siguiente del mismo día.
            const sig = items[i + 1];
            const modo = this._modoDe(it);
            let txt = '', listo = false;
            if (sig) {
              if (this.PLAN_ID) {
                const g = this._segs ? this._segs[this._segKey(it, sig)] : undefined;
                if (g && g.ok) {
                  txt = [this._fmtDur(g.s), this._fmtDist(g.m)].filter(Boolean).join(' • ');
                  listo = true;
                } else if (g) {
                  txt = 'Sin ruta directa';      // Google dice que no hay camino
                  listo = true;
                } else if (!this._rutasApagadas) {
                  txt = 'Calculando…';
                }
                // Con el servicio caído no se pone nada: mejor una fila
                // vacía que un tiempo inventado o un "sin ruta" que miente.
              } else if (it.travel) {
                txt = it.travel.t; listo = true;   // datos del prototipo sin servidor
              }
            }
            const modoDemo = it.travel ? (it.travel.mode === 'car' ? 'DRIVE' : 'WALK') : 'DRIVE';
            const mEfec = this.PLAN_ID ? modo : modoDemo;
            return {
              // La fila existe siempre que haya un lugar siguiente: así la
              // línea punteada y el selector de transporte no desaparecen
              // aunque todavía no se sepa el tiempo.
              hasTravel: !!sig,
              travel: txt,
              travelOp: listo ? '1' : '.55',     // "Calculando…" va más apagado
              walk: mEfec === 'WALK', car: mEfec === 'DRIVE', bike: mEfec === 'BICYCLE',
              modoOpen: s.modoMenu === it.uid,
              modoToggle: (e) => {
                e.preventDefault(); e.stopPropagation();
                this.setState({ modoMenu: s.modoMenu === it.uid ? null : it.uid });
              },
              modos: [
                { k: 'WALK', t: 'A pie' }, { k: 'DRIVE', t: 'En coche' }, { k: 'BICYCLE', t: 'En bicicleta' }
              ].map(m => ({
                t: m.t, on: mEfec === m.k,
                bg: mEfec === m.k ? '#EFF3F6' : 'transparent',
                esWalk: m.k === 'WALK', esCar: m.k === 'DRIVE', esBike: m.k === 'BICYCLE',
                pick: (e) => { e.preventDefault(); this._setModo(di, it, m.k); }
              })),
              lineB: sig ? '2px dashed #CCCCCC' : 'none'
            };
          })(),
          hasReacts: !!(it.reacts && it.reacts.length), noReacts: !(it.reacts && it.reacts.length),
          quickEmos: ['👍', '❤️', '🎉'].map(pe => ({
            e: pe,
            pick: () => this.reaccionar(di, it.uid, pe)
          })),
          reChips: (it.reacts || []).map((r) => ({
            label: r.e + ' ' + r.n,
            bd: r.mine ? '#B9BEF0' : '#DEE2E6', bg: r.mine ? '#EEF0FE' : '#ffffff',
            click: () => this.reaccionar(di, it.uid, r.e)
          })),
          pickToggle: (e) => {
            if (this.state.emoPicker === it.uid) { this._emoCerrar(); return; }
            this._emoAbrir(di, it.uid, e.currentTarget);
          },
          // Al borrar hay que soltar también la marca de "Añadido":
          // si no, Explorar seguía anunciando el lugar como añadido y
          // el siguiente clic mandaba un del con un id ya inexistente.
          del: () => {
            const days = this.state.dayItems.map(a => [...a]);
            days[di] = days[di].filter(x => x.uid !== it.uid);
            const added = { ...this.state.added };
            if (it.pid) { delete added[it.pid]; if (this._addedSid) delete this._addedSid[it.pid]; }
            this.setState({ dayItems: days, added });
            if (it.sid) this._sync('plan_items.php', { action: 'del', id: it.sid });
            this._reproject();
          },
          dragStart: (e) => { this.setState({ drag: { day: di, uid: it.uid } }); try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', it.uid); } catch (err) {} },
          dragEnd: () => this.setState({ drag: null, dragOver: null })
        })),
        ...(() => { const g = mkGap(di, items.length); return { endH: g.gapH, endB: g.gapB, endBg: g.gapBg, endOver: g.gapOver, endLeave: g.gapLeave, endDrop: g.gapDrop }; })(),
        hasChecklist: di === 0 && !!s.dayCk,
        ckDel: () => this.setState({ dayCk: null }),
        ckTitle: s.dayCk ? s.dayCk.title : '',
        ckItems: (di === 0 && s.dayCk ? s.dayCk.items : []).map((c, ci) => ckVals(c,
          () => this.setState({ dayCk: { ...this.state.dayCk, items: this.state.dayCk.items.map((x, j) => j === ci ? { ...x, done: !x.done } : x) } }),
          () => this.setState({ dayCk: { ...this.state.dayCk, items: this.state.dayCk.items.filter((x, j) => j !== ci) } }))),
        openTpl: () => this.setState({ tplOpen: 'day', tplTab: 0 }),
        ckNew: s.dayCk ? s.dayCk.newTxt : '',
        ckNewChange: (e) => this.setState({ dayCk: { ...this.state.dayCk, newTxt: e.target.value } }),
        ckNewKey: (e) => { const ck = this.state.dayCk; if (e.key === 'Enter' && ck.newTxt.trim()) this.setState({ dayCk: { ...ck, items: [...ck.items, { t: ck.newTxt.trim(), done: false }], newTxt: '' } }); },
        hasNote: di === 0 && !!s.dayNote, note: s.dayNote,
        noteDel: () => this.setState({ dayNote: '' }),
        // ── Buscador de lugares del día (autocompletado de Google) ──
        placeTxt: s.placeTxts[di],
        placeChange: (e) => {
          const v = e.target.value;
          const a = [...this.state.placeTxts]; a[di] = v; this.setState({ placeTxts: a });
          this._acBuscar('d:' + di, v);
        },
        placeKey: (e) => {
          if (e.key === 'Escape') { this.setState({ acKey: null, acItems: [] }); return; }
          if (e.key !== 'Enter') return;
          const txt = (this.state.placeTxts[di] || '').trim();
          if (!txt) return;
          const limpiar = () => { const a = [...this.state.placeTxts]; a[di] = ''; this.setState({ placeTxts: a }); };
          const sug = (this.state.acKey === 'd:' + di) ? this.state.acItems : [];
          if (sug.length) {
            // Enter elige la primera sugerencia (como Wanderlog)
            this._acElegir('d:' + di, sug[0].pid, sug[0].main, (nom, ex) => addToDay(di, nom, ex));
          } else {
            addToDay(di, txt);
          }
          limpiar();
        },
        acOpen: s.acKey === 'd:' + di && s.acItems.length > 0,
        acItems: (s.acKey === 'd:' + di ? s.acItems : []).map(it => ({
          main: it.main, sec: it.sec,
          pick: (e) => {
            if (e && e.preventDefault) e.preventDefault();
            const a = [...this.state.placeTxts]; a[di] = ''; this.setState({ placeTxts: a });
            this._acElegir('d:' + di, it.pid, it.main, (nom, ex) => addToDay(di, nom, ex));
          }
        })),
        acClose: () => setTimeout(() => { if (this.state.acKey === 'd:' + di) this.setState({ acKey: null, acItems: [] }); }, 150),
        // Mismo gesto que en Resumen: al enfocar, el buscador se
        // estira y los dos botones de la derecha se pliegan. La clave
        // va con prefijo 'd' para no chocar con los ids de las listas.
        placeFocus: () => this.setState({ placeFocusId: 'd' + di }),
        placeBlur: () => {
          if (this.state.placeFocusId === 'd' + di) this.setState({ placeFocusId: null });
          setTimeout(() => { if (this.state.acKey === 'd:' + di) this.setState({ acKey: null, acItems: [] }); }, 150);
        },
        btnW: s.placeFocusId === 'd' + di ? '0px' : '42px',
        btnML: s.placeFocusId === 'd' + di ? '-9px' : '0px',
        btnOp: s.placeFocusId === 'd' + di ? '0' : '1',
        recsOpen: !!s.dayRecsOpen[di], recsRot: s.dayRecsOpen[di] ? '0deg' : '-90deg',
        recsToggle: () => this.setState({ dayRecsOpen: { ...this.state.dayRecsOpen, [di]: !this.state.dayRecsOpen[di] } }),
        recs: RECS.map(rc => ({ ...rc, add: () => addToDay(di, rc.name, rc) }))
      };
    });

    // ── Presupuesto ──
    const CATC = { Alojamiento: '#8e44ad', Comida: '#d97706', Actividades: '#41A24D', Gasolina: '#E7AD00', Transporte: '#1E86D8', Compras: '#e02424', Otro: '#6b7a83' };
    const CATI = {
      Alojamiento: 'M64 96C81.7 96 96 110.3 96 128L96 352L320 352L320 224C320 206.3 334.3 192 352 192L512 192C565 192 608 235 608 288L608 512C608 529.7 593.7 544 576 544C558.3 544 544 529.7 544 512L544 448L96 448L96 512C96 529.7 81.7 544 64 544C46.3 544 32 529.7 32 512L32 128C32 110.3 46.3 96 64 96zM144 256C144 220.7 172.7 192 208 192C243.3 192 272 220.7 272 256C272 291.3 243.3 320 208 320C172.7 320 144 291.3 144 256z',
      Comida: 'M127.9 78.4C127.1 70.2 120.2 64 112 64C103.8 64 96.9 70.2 96 78.3L81.9 213.7C80.6 219.7 80 225.8 80 231.9C80 277.8 115.1 315.5 160 319.6L160 544C160 561.7 174.3 576 192 576C209.7 576 224 561.7 224 544L224 319.6C268.9 315.5 304 277.8 304 231.9C304 225.8 303.4 219.7 302.1 213.7L287.9 78.3C287.1 70.2 280.2 64 272 64C263.8 64 256.9 70.2 256.1 78.4L242.5 213.9C241.9 219.6 237.1 224 231.4 224C225.6 224 220.8 219.6 220.2 213.8L207.9 78.6C207.2 70.3 200.3 64 192 64C183.7 64 176.8 70.3 176.1 78.6L163.8 213.8C163.3 219.6 158.4 224 152.6 224C146.8 224 142 219.6 141.5 213.9L127.9 78.4zM512 64C496 64 384 96 384 240L384 352C384 387.3 412.7 416 448 416L480 416L480 544C480 561.7 494.3 576 512 576C529.7 576 544 561.7 544 544L544 96C544 78.3 529.7 64 512 64z',
      Actividades: 'M335.9 84.2C326.1 78.6 314 78.6 304.1 84.2L80.1 212.2C67.5 219.4 61.3 234.2 65 248.2C68.7 262.2 81.5 272 96 272L128 272L128 480L128 480L76.8 518.4C68.7 524.4 64 533.9 64 544C64 561.7 78.3 576 96 576L544 576C561.7 576 576 561.7 576 544C576 533.9 571.3 524.4 563.2 518.4L512 480L512 272L544 272C558.5 272 571.2 262.2 574.9 248.2C578.6 234.2 572.4 219.4 559.8 212.2L335.8 84.2zM464 272L464 480L400 480L400 272L464 272zM352 272L352 480L288 480L288 272L352 272zM240 272L240 480L176 480L176 272L240 272zM320 160C337.7 160 352 174.3 352 192C352 209.7 337.7 224 320 224C302.3 224 288 209.7 288 192C288 174.3 302.3 160 320 160z',
      Gasolina: 'M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z',
      Transporte: 'M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 352C428.2 352 464 387.8 464 432L464 444C464 455 473 464 484 464C495 464 504 455 504 444L504 316.3C471.5 306.1 448 275.8 448 240L448 208C448 199.2 455.2 192 464 192L480 192L480 144C480 135.2 487.2 128 496 128C504.8 128 512 135.2 512 144L512 192L544 192L544 144C544 135.2 551.2 128 560 128C568.8 128 576 135.2 576 144L576 192L592 192C600.8 192 608 199.2 608 208L608 240C608 275.8 584.5 306.1 552 316.3L552 444C552 481.6 521.6 512 484 512C446.4 512 416 481.6 416 444L416 432C416 414.3 401.7 400 384 400L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM178.7 253.7L217.7 253.7L196.8 320.6C194.4 328.2 200.1 336 208.1 336C211 336 213.7 335 215.9 333.1L310.5 251.1C313.6 248.4 315.4 244.5 315.4 240.4C315.4 232.6 309.1 226.3 301.3 226.3L262.3 226.3L283.2 159.4C285.6 151.8 279.9 144 271.9 144C269 144 266.3 145 264.1 146.9L169.5 228.9C166.4 231.6 164.6 235.5 164.6 239.6C164.6 247.4 170.9 253.7 178.7 253.7z',
      Compras: 'M256 144C256 108.7 284.7 80 320 80C355.3 80 384 108.7 384 144L384 192L256 192L256 144zM208 192L144 192C117.5 192 96 213.5 96 240L96 448C96 501 139 544 192 544L448 544C501 544 544 501 544 448L544 240C544 213.5 522.5 192 496 192L432 192L432 144C432 82.1 381.9 32 320 32C258.1 32 208 82.1 208 144L208 192zM232 240C245.3 240 256 250.7 256 264C256 277.3 245.3 288 232 288C218.7 288 208 277.3 208 264C208 250.7 218.7 240 232 240zM384 264C384 250.7 394.7 240 408 240C421.3 240 432 250.7 432 264C432 277.3 421.3 288 408 288C394.7 288 384 277.3 384 264z',
      Otro: 'M296 88C296 74.7 306.7 64 320 64C333.3 64 344 74.7 344 88L344 128L400 128C417.7 128 432 142.3 432 160C432 177.7 417.7 192 400 192L285.1 192C260.2 192 240 212.2 240 237.1C240 259.6 256.5 278.6 278.7 281.8L370.3 294.9C424.1 302.6 464 348.6 464 402.9C464 463.2 415.1 512 354.9 512L344 512L344 552C344 565.3 333.3 576 320 576C306.7 576 296 565.3 296 552L296 512L224 512C206.3 512 192 497.7 192 480C192 462.3 206.3 448 224 448L354.9 448C379.8 448 400 427.8 400 402.9C400 380.4 383.5 361.4 361.3 358.2L269.7 345.1C215.9 337.5 176 291.4 176 237.1C176 176.9 224.9 128 285.1 128L296 128L296 88z'
    };
    const moneyMXN = (v, dec) => v.toLocaleString('es-MX', { minimumFractionDigits: dec === undefined ? 2 : dec, maximumFractionDigits: dec === undefined ? 2 : dec }) + ' MXN';
    let total = 0; const catSum = {};
    for (const g of s.gastos) { total += g.m; catSum[g.cat] = (catSum[g.cat] || 0) + g.m; }
    V.budTotal = moneyMXN(total);
    V.budLimit = moneyMXN(s.budget, 0);
    V.budPct = Math.min(100, (total / s.budget) * 100).toFixed(1) + '%';
    V.budBar = total > s.budget ? '#e02424' : '#41A24D';
    V.budEdit = s.budgetEdit; V.budEditOff = !s.budgetEdit;
    V.budEditStart = () => this.setState({ budgetEdit: true, budgetTxt: String(this.state.budget) });
    V.budTxt = s.budgetTxt;
    V.budTxtChange = (e) => this.setState({ budgetTxt: e.target.value });
    const saveBud = () => { const v = parseFloat(this.state.budgetTxt); if (v > 0) { this.setState({ budget: v, budgetEdit: false }); this._sync('plan_update.php', { presupuesto: v }); } else this.setState({ budgetEdit: false }); };
    V.budSave = saveBud;
    V.budKey = (e) => { if (e.key === 'Enter') saveBud(); };
    V.heroMenuOpen = s.heroMenuOpen;
    V.heroMenuToggle = () => this.setState({ heroMenuOpen: !s.heroMenuOpen });
    V.heroMenuClose = () => this.setState({ heroMenuOpen: false });
    const anySecOpen = s.lists.some(l => l.open) || Object.values(s.dayOpen || {}).some(Boolean) || s.gastosOpen;
    V.allSecLabel = anySecOpen ? 'Contraer todas las secciones' : 'Expandir todas las secciones';
    V.allSecRot = anySecOpen ? '180deg' : '0deg';
    V.allSecToggle = () => {
      const to = !anySecOpen;
      const dOpen = {};
      this.DAYS.forEach((_, i) => { dOpen[i] = to; });
      this.setState({
        lists: this.state.lists.map(l => ({ ...l, open: to })),
        dayOpen: dOpen,
        gastosOpen: to
      });
    };
    V.desglose = s.desglose;
    V.desgloseToggle = () => this.setState({ desglose: !s.desglose });
    V.desgClose = () => this.setState({ desglose: false });
    // ── Plantillas de listas predefinidas ──
    V.tplOn = !!s.tplOpen;
    const tplReset = { tplOpen: null, tplSel: {}, tplExp: {} };
    V.tplClose = () => this.setState(tplReset);
    const TPL = window.RN_PLANTILLAS || { empaque: [], tareas: [] };
    const tKey = s.tplTab === 1 ? 'tareas' : 'empaque';
    V.tplTabs = [{ n: 'Listas de empaque', k: 0 }, { n: 'Tareas prevías', k: 1 }].map(tb => ({
      n: tb.n, bd: s.tplTab === tb.k ? '#DEE2E6' : 'transparent', bg: s.tplTab === tb.k ? '#ffffff' : 'transparent',
      click: () => this.setState({ tplTab: tb.k })
    }));
    const selKey = (ci, ii) => tKey + '|' + ci + '|' + ii;
    V.tplCats = (TPL[tKey] || []).map((cat, ci) => {
      const onCnt = cat.items.reduce((a, _, ii) => a + (s.tplSel[selKey(ci, ii)] ? 1 : 0), 0);
      const all = onCnt > 0 && onCnt === cat.items.length, some = onCnt > 0 && !all;
      const ek = tKey + '|' + ci, isOpen = !!s.tplExp[ek];
      return {
        n: cat.n, isOpen, rot: isOpen ? '90deg' : '0deg', all, some,
        open: () => this.setState({ tplExp: { ...s.tplExp, [ek]: !isOpen } }),
        boxBd: (all || some) ? '#E7AD00' : '#ADB5BD', boxBg: all ? '#E7AD00' : '#ffffff',
        toggleAll: () => { const sel = { ...s.tplSel }; cat.items.forEach((_, ii) => { if (all) delete sel[selKey(ci, ii)]; else sel[selKey(ci, ii)] = true; }); this.setState({ tplSel: sel }); },
        items: cat.items.map((t, ii) => { const on = !!s.tplSel[selKey(ci, ii)]; return {
          t, on, bd: on ? '#E7AD00' : '#ADB5BD', bg: on ? '#E7AD00' : '#ffffff',
          toggle: () => { const sel = { ...s.tplSel }; if (on) delete sel[selKey(ci, ii)]; else sel[selKey(ci, ii)] = true; this.setState({ tplSel: sel }); }
        }; })
      };
    });
    const tplPicked = [];
    ['empaque', 'tareas'].forEach(k => (TPL[k] || []).forEach((cat, ci) => cat.items.forEach((t, ii) => { if (s.tplSel[k + '|' + ci + '|' + ii]) tplPicked.push(t); })));
    const tplN = tplPicked.length;
    V.tplBtn = tplN === 0 ? 'Añadir a lista de verificación' : ('Añadir ' + tplN + (tplN === 1 ? ' elemento' : ' elementos') + ' a la lista de verificación');
    V.tplOp = tplN === 0 ? '.55' : '1';
    V.tplCur = tplN === 0 ? 'default' : 'pointer';
    V.tplAdd = () => {
      if (!tplN) return;
      const add = tplPicked.map(t => ({ t, done: false }));
      const tgt = this.state.tplOpen;
      if (tgt === 'day' && this.state.dayCk) this.setState({ dayCk: { ...this.state.dayCk, items: [...this.state.dayCk.items, ...add] }, ...tplReset });
      else if (tgt && tgt.li !== undefined) {
        const lista = this.state.lists[tgt.li];
        this.setState({ lists: this.state.lists.map((x, j) => j === tgt.li ? { ...x, items: [...(x.items || []), ...add] } : x), ...tplReset });
        if (lista && typeof lista.id === 'number') {
          this._sync('plan_listas.php', { action: 'import_plantilla', id: lista.id, textos: tplPicked }, () => this._reloadListas());
        }
      }
      else this.setState(tplReset);
    };
    const isCat = (s.desgTab || 'cat') === 'cat';
    V.desgIsCat = isCat; V.desgIsDia = !isCat;
    const tabSt = (on) => ({ bg: on ? '#ffffff' : 'transparent', bd: on ? '#DEE2E6' : 'transparent', w: on ? 700 : 500 });
    const t1 = tabSt(isCat), t2 = tabSt(!isCat);
    V.desgCatBg = t1.bg; V.desgCatBd = t1.bd; V.desgCatW = t1.w;
    V.desgDiaBg = t2.bg; V.desgDiaBd = t2.bd; V.desgDiaW = t2.w;
    V.desgCatGo = () => this.setState({ desgTab: 'cat' });
    V.desgDiaGo = () => this.setState({ desgTab: 'dia' });
    let desgData;
    if (isCat) {
      desgData = ['Vuelos', 'Alquiler de coches', 'Transporte', 'Bebidas', 'Comestibles', 'Turismo', 'Gasolina', 'Compras', 'Actividades', 'Comida', 'Alojamiento', 'Otro'].map(c => ({ label: c, v: catSum[c] || 0 }));
    } else {
      const byDay = {};
      for (const g of s.gastos) { const f = g.fecha || ''; byDay[f] = (byDay[f] || 0) + g.m; }
      const dated = this.DAYS.filter(d => d.iso);
      if (dated.length) {
        desgData = dated.map(d => { const a = d.iso.split('-'); return { label: (+a[2]) + '/' + (+a[1]), v: byDay[this._fmtDia(d.iso)] || 0 }; });
        // gastos con fecha fuera del rango del viaje
        const inTrip = new Set(dated.map(d => this._fmtDia(d.iso)));
        let otros = 0;
        Object.keys(byDay).forEach(f => { if (!inTrip.has(f)) otros += byDay[f]; });
        if (otros > 0) desgData.push({ label: 'Otros', v: otros });
      } else {
        desgData = Object.keys(byDay).map(f => ({ label: f || 'Sin fecha', v: byDay[f] }));
        if (!desgData.length) desgData = [{ label: '—', v: 0 }];
      }
    }
    const desgMax = Math.max(2000, Math.ceil(Math.max(1, ...desgData.map(r => r.v)) / 2000) * 2000);
    const fmtAx = (n) => 'MX$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    V.desgRows2 = desgData.map(r => {
      const pctN = (r.v / desgMax) * 100;
      return { label: r.label, name: r.label, amt: fmtAx(r.v), pct: pctN.toFixed(2) + '%', tipL: Math.min(62, Math.max(8, pctN * 0.6)).toFixed(1) + '%', h: isCat ? '34px' : '54px', barH: isCat ? '22px' : '26px' };
    });
    V.desgAx0 = fmtAx(0); V.desgAxMid = fmtAx(desgMax / 2); V.desgAxMax = fmtAx(desgMax);
    V.desgLabW = isCat ? '112px' : '46px'; V.desgAxOff = isCat ? '120px' : '54px'; V.desgAxOffC = isCat ? '121px' : '55px';
    V.expFormOpen = s.expFormOpen;
    V.expFormToggle = () => this.setState({ expFormOpen: !s.expFormOpen });
    V.expC = s.expC; V.expCChange = (e) => this.setState({ expC: e.target.value });
    V.expM = s.expM; V.expMChange = (e) => this.setState({ expM: e.target.value });
    V.expCat = s.expCat; V.expCatChange = (e) => this.setState({ expCat: e.target.value });
    V.expSave = () => {
      const v = parseFloat(this.state.expM);
      if (!this.state.expC.trim() || !(v > 0)) return;
      const hoy = new Date();
      const iso = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
      const tmpId = 'g' + Date.now();
      const g = { id: tmpId, c: this.state.expC.trim(), m: v, cat: this.state.expCat, fecha: this._fmtDia(iso), fiso: iso, ts: Date.now() };
      this.setState({ gastos: [...this.state.gastos, g], expC: '', expM: '', expFormOpen: false });
      this._sync('plan_gastos.php', { action: 'add', concepto: g.c, monto: v, categoria: g.cat, fecha: iso }, (j) => {
        this.setState({ gastos: this.state.gastos.map(x => x.id === tmpId ? { ...x, id: Number(j.id) } : x) });
      });
    };
    V.expCancel = () => this.setState({ expFormOpen: false, expC: '', expM: '' });
    V.gastosOpen = s.gastosOpen; V.gastosRot = s.gastosOpen ? '90deg' : '0deg';
    V.gastosToggle = () => this.setState({ gastosOpen: !s.gastosOpen });
    V.gastosEmpty = s.gastos.length === 0;
    V.expSort = s.expSort;
    V.expSortChange = (e) => this.setState({ expSort: e.target.value });
    const gastoCmp = (a, b) => {
      switch (s.expSort) {
        case 'fechaAsc': return a.ts - b.ts;
        case 'monto': return b.m - a.m;
        case 'montoAsc': return a.m - b.m;
        case 'cat': return a.cat.localeCompare(b.cat, 'es');
        default: return b.ts - a.ts;
      }
    };
    V.gastoRows = [...s.gastos].sort(gastoCmp).map(g => ({
      c: g.c, sub: (g.fecha || '31 jul.') + ' • ' + g.cat, iconD: CATI[g.cat] || CATI.Otro,
      amt: 'MXN ' + g.m.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
      remove: () => { this.setState({ gastos: this.state.gastos.filter(x => x.id !== g.id) }); if (typeof g.id === 'number') this._sync('plan_gastos.php', { action: 'del', id: g.id }); }
    }));

    // ── Asistente ──
    V.chatSmall = s.chatMode === 'small' && !s.narrow;
    V.chatToFull = () => this.openChatFull();
    V.chatToSmall = () => { if (this.state.chatMode === 'full') this.closeChatFull('small'); else this.setState({ chatMode: 'small' }); };
    V.chatClose = () => { if (this.state.chatMode === 'full') this.closeChatFull(null); else this.setState({ chatMode: null }); };
    V.chatReset = () => {
      clearInterval(this._si);
      this.setState({ chatLog: [], streaming: false, streamTxt: '', chatInput: '' });
      // El hilo también vive en la sesión del servidor: si no se avisa,
      // la pantalla queda vacía pero el modelo sigue recordando todo.
      if (this.PLAN_ID) {
        fetch('api/plan_ai.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': this.CSRF },
          body: JSON.stringify({ plan_id: this.PLAN_ID, reset: 1 })
        }).catch(() => {});
      }
    };
    V.chatFresh = s.chatLog.length === 0 && !s.streaming;
    V.chatSugs = this.CHIPS_SUG.map(t => ({ t, pick: () => this.sendChat(t) }));
    V.chatInput = s.chatInput;
    V.chatInputChange = (e) => this.setState({ chatInput: e.target.value });
    V.chatSubmit = (e) => { e.preventDefault(); this.sendChat(); };
    V.chatPh = 'Pida información relacionada con viajes como «¿La mejor comida en ' + this.META.destino + '?»';
    V.streaming = s.streaming;
    const richBody = (src) => {
      // parse **bold**, [enlaces], saltos de línea y viñetas
      const out = []; let key = 0;
      const lines = src.split('\n');
      lines.forEach((line, li) => {
        const isBullet = line.startsWith('• ');
        const txt = isBullet ? line.slice(2) : line;
        const parts = [];
        let rest = txt;
        while (rest.length) {
          const mB = rest.match(/\*\*(.+?)\*\*/); const mL = rest.match(/\[(.+?)\]/);
          const iB = mB ? rest.indexOf(mB[0]) : -1; const iL = mL ? rest.indexOf(mL[0]) : -1;
          let next = null, at = -1;
          if (iB >= 0 && (iL < 0 || iB <= iL)) { next = 'b'; at = iB; } else if (iL >= 0) { next = 'l'; at = iL; }
          if (next === null) { parts.push(rest); break; }
          if (at > 0) parts.push(rest.slice(0, at));
          if (next === 'b') { parts.push(React.createElement('b', { key: 'k' + (key++), style: { color: '#0D1F27' } }, mB[1])); rest = rest.slice(at + mB[0].length); }
          else {
            const nm = mL[1];
            parts.push(React.createElement('a', {
              key: 'k' + (key++), href: '#',
              onClick: (e) => e.preventDefault(),
              onMouseEnter: () => { const p = this.PLACES.find(pp => pp.name === nm || nm.includes(pp.name) || pp.name.includes(nm)); if (p) this.setState({ hoverPlace: p.id }); },
              onMouseLeave: () => this.setState({ hoverPlace: null }),
              style: { color: '#1A73C8', textDecoration: 'none', fontWeight: 500 }
            }, nm));
            rest = rest.slice(at + mL[0].length);
          }
        }
        if (isBullet) out.push(React.createElement('div', { key: 'ln' + li, style: { display: 'flex', gap: '8px', margin: '2px 0 2px 4px' } }, React.createElement('span', { style: { flexShrink: 0 } }, '•'), React.createElement('span', null, parts)));
        else if (txt.trim() === '') out.push(React.createElement('div', { key: 'ln' + li, style: { height: '8px' } }));
        else out.push(React.createElement('div', { key: 'ln' + li }, parts));
      });
      return React.createElement('div', null, out);
    };
    V.chatMsgs = s.chatLog.map((m, i) => ({
      isUser: m.who === 'u', isAi: m.who === 'a', t: m.t,
      body: m.who === 'a' ? richBody(m.t) : null,
      showActions: m.who === 'a' && i === s.chatLog.length - 1
    }));
    V.streamBody = richBody(s.streamTxt);

    // ── Mapa ──
    V.mapModalOn = s.narrow && s.mapModal;
    V.mapModalClose = () => this.setState({ mapModal: false });
    // Los POIs decorativos mueren con el SVG: el basemap de Google ya los pinta
    V.mapPois = [];
    const layerOn = (k) => s.layerChecks[k] !== false;
    // Pines del itinerario proyectados (lat/lng → px del contenedor)
    const pinPx = this._pinPx || {};
    V.mapPins = [];
    (s.dayItems || []).forEach((arr, di) => {
      if (!layerOn('d' + di)) return;
      arr.forEach((it, i) => {
        const p = pinPx[it.uid];
        if (!p) return;
        // También se resalta el pin del lugar que enseña la ficha, no
        // sólo el que está bajo el ratón: al pasar de un sitio a otro
        // con las flechas hay que ver a cuál corresponde la ficha.
        const hot = s.hoverPlace === it.uid || s.detail === (it.pid || it.uid);
        V.mapPins.push({
          // el número sigue al orden del día en el Itinerario (se
          // recalcula solo al arrastrar y soltar una tarjeta)
          num: String(i + 1), name: it.name,
          left: p.left + 'px', top: p.top + 'px',
          fill: this.DAYS[di] ? this.DAYS[di].color : this.PIN.sav,
          hover: hot, z: hot ? 55 : 40,
          recent: false,
          // Clic en el pin: abre la ficha del lugar (y si es un lugar
          // escrito a mano, al menos centra el mapa en él)
          click: () => {
            this._centrarEn(it);
            this.openDetail(it.pid || it.uid, null, 'itin');
          },
          enter: () => this.setState({ hoverPlace: it.uid }),
          leave: () => { if (this.state.hoverPlace === it.uid) this.setState({ hoverPlace: null }); }
        });
      });
    });
    // Pines de los lugares de Explorar (Places reales; capas top/eat/stay).
    // Llevan el MISMO número que su fila. Si el buscador de Explorar dejó
    // fuera un lugar, su pin también se va: de lo contrario quedarían dos
    // pines con el mismo número y ninguno correspondería a la lista.
    (this.PLACES || []).forEach(p => {
      const n = exNum[p.id] || (exq ? 0 : (p.num || 0));
      if (!n) return;
      const pp = pinPx[p.id];
      if (!pp) return;
      const hot = s.hoverPlace === p.id || s.detail === p.id;
      V.mapPins.push({
        num: String(n), name: p.name,
        left: pp.left + 'px', top: pp.top + 'px',
        fill: this.PIN[p.cat],
        hover: hot, z: hot ? 55 : 40,
        recent: !!p.recent,
        click: () => this.openDetail(p.id),
        enter: () => this.setState({ hoverPlace: p.id }),
        leave: () => { if (this.state.hoverPlace === p.id) this.setState({ hoverPlace: null }); }
      });
    });
    // Pines teal de los resultados del buscador del mapa (M4)
    (this.SEARCH || []).forEach(p => {
      if ((this.PLACES || []).some(x => x.id === p.id)) return;
      const pp = pinPx[p.id];
      if (!pp) return;
      const hot = s.hoverPlace === p.id || s.detail === p.id;
      V.mapPins.push({
        num: String(p.num), name: p.name,
        left: pp.left + 'px', top: pp.top + 'px',
        fill: this.PIN.sav,
        hover: hot, z: hot ? 55 : 42,
        recent: false,
        click: () => this.openDetail(p.id),
        enter: () => this.setState({ hoverPlace: p.id }),
        leave: () => { if (this.state.hoverPlace === p.id) this.setState({ hoverPlace: null }); }
      });
    });
    // La ruta ahora es Polyline nativa (_updateRoute); el polyline del SVG ya no existe
    V.routeShow = false;
    V.routePts = '';
    V.zoomIn = () => { if (this._map) this._map.setZoom(this._map.getZoom() + 1); };
    V.zoomOut = () => { if (this._map) this._map.setZoom(this._map.getZoom() - 1); };
    V.fitAll = () => this.fitAllPins();
    V.fitDetail = () => {
      const d = s.detail ? this.place(s.detail) : null;
      if (d && d.lat != null && this._map) { this._map.panTo({ lat: d.lat, lng: d.lng }); this._map.setZoom(16); }
    };
    V.mapSearchOpen = s.mapSearchOpen; V.mapSearchClosed = !s.mapSearchOpen;
    V.mapSearchToggle = () => this.setState({ mapSearchOpen: !s.mapSearchOpen, layersOpen: false });
    V.mapSearchQ = s.mapSearchQ;
    V.mapSearchQChange = (e) => this.setState({ mapSearchQ: e.target.value });
    V.mapSearchKey = (e) => { if (e.key === 'Enter' && this.state.mapSearchQ.trim()) this._mapSearch(this.state.mapSearchQ); };
    V.mapSearchCats = [
      { d: 'M127.9 78.4C127.1 70.2 120.2 64 112 64C103.8 64 96.9 70.2 96 78.3L81.9 213.7C80.6 219.7 80 225.8 80 231.9C80 277.8 115.1 315.5 160 319.6L160 544C160 561.7 174.3 576 192 576C209.7 576 224 561.7 224 544L224 319.6C268.9 315.5 304 277.8 304 231.9C304 225.8 303.4 219.7 302.1 213.7L287.9 78.3C287.1 70.2 280.2 64 272 64C263.8 64 256.9 70.2 256.1 78.4L242.5 213.9C241.9 219.6 237.1 224 231.4 224C225.6 224 220.8 219.6 220.2 213.8L207.9 78.6C207.2 70.3 200.3 64 192 64C183.7 64 176.8 70.3 176.1 78.6L163.8 213.8C163.3 219.6 158.4 224 152.6 224C146.8 224 142 219.6 141.5 213.9L127.9 78.4zM512 64C496 64 384 96 384 240L384 352C384 387.3 412.7 416 448 416L480 416L480 544C480 561.7 494.3 576 512 576C529.7 576 544 561.7 544 544L544 96C544 78.3 529.7 64 512 64z', t: 'Comida' },
      { d: 'M352 348.4C416.1 333.9 464 276.5 464 208C464 128.5 399.5 64 320 64C240.5 64 176 128.5 176 208C176 276.5 223.9 333.9 288 348.4L288 544C288 561.7 302.3 576 320 576C337.7 576 352 561.7 352 544L352 348.4zM328 160C297.1 160 272 185.1 272 216C272 229.3 261.3 240 248 240C234.7 240 224 229.3 224 216C224 158.6 270.6 112 328 112C341.3 112 352 122.7 352 136C352 149.3 341.3 160 328 160z', t: 'Atracciones' },
      { d: 'M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z', t: 'Gasolinera' },
      { d: 'M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 352C428.2 352 464 387.8 464 432L464 444C464 455 473 464 484 464C495 464 504 455 504 444L504 316.3C471.5 306.1 448 275.8 448 240L448 208C448 199.2 455.2 192 464 192L480 192L480 144C480 135.2 487.2 128 496 128C504.8 128 512 135.2 512 144L512 192L544 192L544 144C544 135.2 551.2 128 560 128C568.8 128 576 135.2 576 144L576 192L592 192C600.8 192 608 199.2 608 208L608 240C608 275.8 584.5 306.1 552 316.3L552 444C552 481.6 521.6 512 484 512C446.4 512 416 481.6 416 444L416 432C416 414.3 401.7 400 384 400L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM178.7 253.7L217.7 253.7L196.8 320.6C194.4 328.2 200.1 336 208.1 336C211 336 213.7 335 215.9 333.1L310.5 251.1C313.6 248.4 315.4 244.5 315.4 240.4C315.4 232.6 309.1 226.3 301.3 226.3L262.3 226.3L283.2 159.4C285.6 151.8 279.9 144 271.9 144C269 144 266.3 145 264.1 146.9L169.5 228.9C166.4 231.6 164.6 235.5 164.6 239.6C164.6 247.4 170.9 253.7 178.7 253.7z', t: 'Carga de vehículos eléctricos' },
      { d: 'M472 64C489.7 64 504 78.3 504 96L600 96C617.7 96 632 110.3 632 128L632 224C632 241.7 617.7 256 600 256L504 256L504 544C504 561.7 489.7 576 472 576C454.3 576 440 561.7 440 544L440 96C440 78.3 454.3 64 472 64zM283.5 123.1L384 220L384 576L128 576C92.7 576 64 547.3 64 512L64 368L44.1 368C28.6 368 16 355.4 16 339.9C16 332.3 19.1 325 24.6 319.7L228.5 123.1C235.9 116 245.7 112 256 112C266.3 112 276.1 116 283.5 123.1zM232 320C218.7 320 208 330.7 208 344L208 392C208 405.3 218.7 416 232 416L280 416C293.3 416 304 405.3 304 392L304 344C304 330.7 293.3 320 280 320L232 320z', t: 'Paradas de descanso' }
    ];
    // M4: cada categoría lanza una búsqueda real en la zona visible del mapa
    const CATQ = {
      'Comida': 'restaurantes', 'Atracciones': 'atracciones turísticas', 'Gasolinera': 'gasolineras',
      'Carga de vehículos eléctricos': 'estaciones de carga para vehículos eléctricos',
      'Paradas de descanso': 'paradas de descanso'
    };
    V.mapSearchCats = V.mapSearchCats.map(c => ({ ...c, pick: () => this._mapSearch(CATQ[c.t] || c.t) }));
    V.layersOpen = s.layersOpen;
    V.layersToggle = () => this.setState({ layersOpen: !s.layersOpen, mapSearchOpen: false });
    const setLayer = (k, v) => this.setState({ layerChecks: { ...this.state.layerChecks, [k]: v } });
    V.layersRes = [
      { t: 'Lugares principales a visitar', color: this.PIN.atr, k: 'top' },
      { t: 'Mejores sitios para comer', color: this.PIN.com, k: 'eat' },
      { t: 'Alojamientos más destacados', color: this.PIN.hot, k: 'stay' }
    ].map(l => ({ ...l, on: layerOn(l.k), toggle: () => setLayer(l.k, !layerOn(l.k)) }));
    V.layersIt = this.DAYS.map((d, i) => ({ t: d.label, color: d.color, k: 'd' + i, i }))
      .map(l => ({
        ...l,
        on: layerOn(l.k),
        toggle: () => setLayer(l.k, !layerOn(l.k)),
        // "Solo" aísla la RUTA de este día sin tocar layerChecks, para que
        // los pines de los demás días sigan a la vista y no se pierda el
        // contexto de dónde está el resto del viaje.
        soloOn: s.rutaSolo === l.i,
        soloTxt: s.rutaSolo === l.i ? 'Ver todas' : 'Solo',
        solo: (e) => { e.preventDefault(); this.setState({ rutaSolo: s.rutaSolo === l.i ? null : l.i, routeLines: true }); }
      }));
    V.rutaSoloOn = s.rutaSolo !== null && s.rutaSolo !== undefined;
    V.rutaSoloTxt = V.rutaSoloOn && this.DAYS[s.rutaSolo]
      ? 'Mostrando sólo la ruta de ' + this.DAYS[s.rutaSolo].label : '';
    V.rutaSoloClear = (e) => { e.preventDefault(); this.setState({ rutaSolo: null }); };
    V.layersAll = (e) => { e.preventDefault(); this.setState({ layerChecks: {}, rutaSolo: null }); };
    V.layersNone = (e) => {
      e.preventDefault();
      const off = { top: false, eat: false, stay: false };
      this.DAYS.forEach((_, i) => { off['d' + i] = false; });
      this.setState({ layerChecks: off });
    };
    V.routeToggle = (e) => {
      e.preventDefault();
      // Apagar las rutas también limpia el aislamiento: si no, al volver a
      // encenderlas reaparecería una sola y parecería que se rompió algo.
      this.setState({ routeLines: !s.routeLines, rutaSolo: s.routeLines ? null : s.rutaSolo });
    };
    V.routeTxt = s.routeLines ? 'Ocultar rutas' : 'Mostrar rutas';
    V.routeOn = !!s.routeLines;

    // ── Detalle ──
    const det = s.detail ? this.place(s.detail) : null;
    V.detailOn = !!det; V.detailOff = !det;
    V.layersBtnShow = !s.mapSearchOpen;
    V.detailClose = () => this.setState({ detail: null });
    // Paginador real: navega entre los lugares cargados (Explorar + búsqueda del mapa)
    const _navExtra = (this.SEARCH || []).filter(p => !this.PLACES.some(x => x.id === p.id));
    const _navAll = this.PLACES.concat(_navExtra);
    const detNav = (this.PLAN_ID && _navAll.length) ? _navAll : null;
    const dIdxNav = detNav && s.detail ? detNav.findIndex(p => p.id === s.detail) : -1;
    // Ficha abierta desde el itinerario: el paginador recorre los
    // lugares de ESE día, en su orden. Antes se quedaba clavado en
    // "1 de 1" con las flechas muertas.
    const diaDet = s.detailFrom === 'itin' ? this._diaDelDetalle(s.detail) : null;
    if (diaDet) {
      V.resIdx = String(diaDet.i + 1);
      V.resTotal = String(diaDet.arr.length);
      // Al cambiar de lugar el mapa lo sigue, para que la ficha y el
      // pin resaltado hablen siempre del mismo sitio.
      const irA = (j) => {
        const it = diaDet.arr[j];
        if (!it) return;
        this.openDetail(it.pid || it.uid, this.state.detailTab, 'itin');
        this._centrarEn(it);
      };
      V.resPrev = () => { if (diaDet.i > 0) irA(diaDet.i - 1); };
      V.resNext = () => { if (diaDet.i < diaDet.arr.length - 1) irA(diaDet.i + 1); };
    } else if (detNav && s.detail && dIdxNav >= 0) {
      V.resIdx = String(dIdxNav + 1);
      V.resTotal = String(detNav.length);
      V.resPrev = () => { if (dIdxNav > 0) this.openDetail(detNav[dIdxNav - 1].id, this.state.detailTab); };
      V.resNext = () => { if (dIdxNav < detNav.length - 1) this.openDetail(detNav[dIdxNav + 1].id, this.state.detailTab); };
    } else if (s.detail) {
      V.resIdx = '1'; V.resTotal = '1';
      V.resPrev = V.noop; V.resNext = V.noop;
    } else {
      V.resIdx = String(s.resIdx);
      V.resTotal = '20';
      V.resPrev = () => this.setState({ resIdx: Math.max(1, s.resIdx - 1) });
      V.resNext = () => this.setState({ resIdx: Math.min(20, s.resIdx + 1) });
    }
    V.detSkel = s.detailLoading; V.detReady = !s.detailLoading;
    V.detTabs = [{ v: 'about', t: 'Acerca de' }, { v: 'rvw', t: 'Reseñas' }, { v: 'fotos', t: 'Fotos' }].map(t => ({
      t: t.t, w: s.detailTab === t.v ? 700 : 500,
      col: s.detailTab === t.v ? '#0D1F27' : '#5b6b74',
      line: s.detailTab === t.v ? '#E7AD00' : 'transparent',
      click: () => this.setState({ detailTab: t.v })
    }));
    V.detTabAbout = s.detailTab === 'about'; V.detTabRvw = s.detailTab === 'rvw'; V.detTabFotos = s.detailTab === 'fotos';
    if (det) {
      V.detName = det.name;
      // Los lugares de Explorar llevan en la ficha el mismo número que en
      // su sección; los del itinerario, el de su posición en el día.
      V.detNum = String(exNum[det.id] && det.sec !== 'itin' && det.sec !== 'search'
        ? exNum[det.id] : det.num);
      V.detPin = det.pinColor || this.PIN[det.cat];
      V.detSeed = det.seed;
      V.detFoto = det.foto || ('https://picsum.photos/seed/' + det.seed + '/280/200');
      V.detChips = det.chips;
      V.detRating = (Number(det.rating) || 0).toFixed(1); V.detRev = (Number(det.rev) || 0).toLocaleString('es-MX');
      const isAdded = !!s.added[det.id];
      V.detAddBg = isAdded ? '#E9ECEF' : '#E7AD00';
      V.detAddCol = isAdded ? '#666F76' : '#FFFFFF';
      V.detAddTxt = isAdded ? 'Añadido' : 'Añadir al plan de viaje';
      // La flecha estaba enlazada a este MISMO handler, así que un
      // control que dice "Elegir día" borraba el lugar del plan.
      V.detAddClick = (e) => this._addAbrir(det.id, e.currentTarget);
      V.detCaretClick = (e) => this._addAbrir(det.id, e.currentTarget);
      // Abre el asistente en panel grande SIN cerrar la ficha: el usuario
      // sigue viendo la información del lugar mientras conversa
      const openChatFromDet = () => this.openChatFull();
      V.detAskAI = openChatFromDet;
      V.detAiChips = ['Guía turística', '¿Cuál es el rango de precios?', 'Necesito una reservación'].map(t => ({ t, pick: () => { openChatFromDet(); setTimeout(() => this.sendChat(t + ' — ' + det.name), 350); } }));
      // ── Datos reales de getDetails (M3); el demo se conserva sin servidor ──
      const dd = (this.PLAN_ID && det.gpid && this._detCache) ? this._detCache[det.gpid] : null;
      const esReal = !!this.PLAN_ID;

      // ── "Acerca de" y de dónde salió ──
      // El demo trae su texto escrito a mano; los lugares reales lo resuelven
      // por la cascada. Mientras llega, se deja vacío en vez de poner un
      // relleno que luego cambia delante del usuario.
      const ac = dd ? dd.acerca : null;
      V.detDesc = esReal ? (ac ? ac.texto : '') : det.desc;
      const FUENTES = {
        // El resumen editorial de Google no es contenido generado por IA y no
        // lleva aviso; la atribución de Google Maps del pie ya lo cubre.
        google: '',
        wikipedia: 'Fuente: Wikipedia',
        plantilla: 'Datos de Google Maps'
      };
      const etiqueta = ac ? (FUENTES[ac.fuente] || '') : '';
      V.hasDetFuente = !!etiqueta;
      V.detFuente = etiqueta;
      V.hasDetFuenteUrl = !!(ac && ac.fuente === 'wikipedia' && ac.url);
      V.detFuenteUrl = (ac && ac.url) || '#';
      V.detFuenteTitulo = (ac && ac.titulo) || '';
      V.hasDetAddress = dd ? !!dd.address : !esReal;
      V.detAddress = dd ? dd.address : 'Blvd. Teniente Azueta 139, Zona Centro, 22800 Ensenada, B.C., México';
      const hoyIdx = new Date().getDay();                     // 0=domingo
      if (dd && dd.weekday.length === 7) {
        // weekday_text va de lunes a domingo
        const wIdx = (hoyIdx + 6) % 7;
        V.hasDetHours = true;
        const linea = dd.weekday[wIdx] || '';
        V.detHoursToday = linea.charAt(0).toUpperCase() + linea.slice(1).replace(/^([a-záéíóú]+):/i, '$1');
        V.detDows = ['do', 'lu', 'ma', 'mi', 'ju', 'vi', 'sá'].map((t, i) => {
          const cerrado = /cerrado/i.test(dd.weekday[(i + 6) % 7] || '');
          return { t, bg: i === hoyIdx ? '#F5B93F' : '#F1F5F8', col: i === hoyIdx ? '#0E2A33' : cerrado ? '#b9c6cf' : '#33454e' };
        });
      } else {
        V.hasDetHours = !esReal;
        V.detHoursToday = 'Viernes 13:00 – 23:00';
        V.detDows = ['do', 'lu', 'ma', 'mi', 'ju', 'vi', 'sá'].map((t, i) => ({ t, bg: i === 5 ? '#F5B93F' : '#F1F5F8', col: i === 5 ? '#0E2A33' : (i === 1 || i === 2) ? '#b9c6cf' : '#33454e' }));
      }
      // Horario completo de la semana, desplegable
      V.detHoursOpen = !!s.detHoursOpen;
      V.detHoursClosed = !s.detHoursOpen;
      V.detHoursTxt = s.detHoursOpen ? 'Ocultar horarios' : 'Mostrar horarios';
      V.detHoursToggle = (e) => { if (e && e.preventDefault) e.preventDefault(); this.setState({ detHoursOpen: !this.state.detHoursOpen }); };
      // Google entrega weekday_text de lunes a domingo; la lista se muestra
      // empezando en domingo, igual que la fila de círculos
      const ORDEN = [6, 0, 1, 2, 3, 4, 5];
      const ABREV = ['do', 'lu', 'ma', 'mi', 'ju', 'vi', 'sá'];
      V.detWeekday = (dd && dd.weekday.length === 7)
        ? ORDEN.map((idx, pos) => ({
            ab: ABREV[pos],
            linea: dd.weekday[idx],
            bg: '#F1F5F8', col: '#5b6b74', w: 400
          }))
        : [];
      V.hasDetDwell = !esReal;                                // sin equivalente en la API
      V.hasDetMentions = !esReal;                             // ídem ("Mencionado por…")
      V.hasDetPhone = dd ? !!dd.phone : !esReal;
      V.detPhone = dd ? dd.phone : '+52 646 175 7073';
      V.hasDetWebsite = dd ? !!dd.website : !esReal;
      V.detWebsite = dd ? dd.website : 'https://www.rmanzanilla.com/';
      const urlMaps = dd ? dd.url : 'https://www.google.com/maps';
      V.detOpenWeb = (e) => { if (e && e.preventDefault) e.preventDefault(); if (dd && dd.website) window.open(dd.website, '_blank', 'noopener'); };
      // "Abrir en Google": búsqueda web normal con el nombre del lugar
      V.detOpenG = () => window.open('https://www.google.com/search?q=' + encodeURIComponent(det.name), '_blank', 'noopener');
      V.detOpenGm = () => window.open(urlMaps, '_blank', 'noopener');
      V.hasDetTips = !esReal;                                 // "Saber antes de ir": sin API
      V.detTips = esReal ? [] : [
        'Visita Manzanilla para una excursión relajada pero deliciosa que incluye catas de vino en el camino.',
        'Prueba sus platos estrella como el chuletón de cerdo con salsa mole o tacos vegetarianos.',
        'Explora tanto las áreas de comedor interiores como exteriores, con un encantador patio.',
        'Prepárate para un servicio más lento durante las horas pico, pero espera personal amable.'
      ];
      const rvwBase = dd ? dd.rating : (esReal ? Number(det.rating) || 0 : 4.1);
      const rvwCnt = dd ? dd.rev : (esReal ? Number(det.rev) || 0 : 1451);
      V.detRvwAvg = rvwBase.toFixed(1);
      V.detRvwLabel = rvwBase >= 4.5 ? 'Excelente' : rvwBase >= 4 ? 'Muy bueno' : rvwBase >= 3 ? 'Bueno' : rvwBase > 0 ? 'Regular' : '';
      V.detRvwCount = rvwCnt.toLocaleString('es-MX') + ' opiniones';
      V.detRvwStars = this._starRow(rvwBase, 15, 14);
      // Histograma de estrellas: Places NO expone la distribución real, solo
      // el promedio, el total y hasta 5 reseñas. Se calcula con esas reseñas
      // y se rotula la muestra para no dar a entender que es el total.
      if (dd && dd.reviews && dd.reviews.length) {
        const cuenta = [0, 0, 0, 0, 0];                       // índice 0 = 5★
        dd.reviews.forEach(r => {
          const e = Math.round(Number(r.stars) || 0);
          if (e >= 1 && e <= 5) cuenta[5 - e] += 1;
        });
        const tope = Math.max.apply(null, cuenta) || 1;
        V.hasDetHisto = true;
        V.detHisto = cuenta.map((n, i) => ({
          s: String(5 - i), n: String(n),
          pct: (n === 0 ? 0 : Math.max(8, (n / tope) * 100)).toFixed(0) + '%'
        }));
        V.detHistoNota = 'Distribución de las ' + dd.reviews.length + ' reseñas que comparte Google';
      } else if (!esReal) {
        const HN = [855, 226, 138, 66, 166];
        V.hasDetHisto = true;
        V.detHisto = HN.map((n, i) => ({ s: String(5 - i), n: String(n), pct: Math.max(8, (n / 855) * 100).toFixed(0) + '%' }));
        V.detHistoNota = '';
      } else {
        V.hasDetHisto = false; V.detHisto = []; V.detHistoNota = '';
      }
      V.detFoto1 = (dd && dd.fotos[0]) || ('https://picsum.photos/seed/' + det.seed + '-f1/400/220');
      V.detFoto2 = (dd && dd.fotos[1]) || ('https://picsum.photos/seed/' + det.seed + '-f2/400/620');
      V.detFoto3 = (dd && dd.fotos[2]) || ('https://picsum.photos/seed/' + det.seed + '-f3/400/560');
      // ── Reseñas: recorte a 380 caracteres con "Ver más / Ver menos" ──
      const TOPE = 380;
      const rvwVM = (r, i) => {
        const txt = r.t || '';
        const abierta = !!(s.rvwOpen || {})[i];
        const larga = txt.length > TOPE;
        return {
          head: r.head, date: r.date,
          t: (larga && !abierta) ? (txt.slice(0, TOPE).trimEnd() + '…') : txt,
          hasMore: larga,
          moreTxt: abierta ? 'Ver menos' : 'Ver más',
          rot: abierta ? '180deg' : '0deg',
          toggle: (e) => {
            if (e && e.preventDefault) e.preventDefault();
            const o = { ...(this.state.rvwOpen || {}) };
            o[i] = !o[i];
            this.setState({ rvwOpen: o });
          }
        };
      };
      // Google Places entrega como máximo 5 reseñas por lugar; el botón
      // "Ver más reseñas" solo aparece si hay más de las que se muestran.
      const PASO = 3, BASE = 5;
      const totalRvw = dd ? dd.reviews.length : 3;
      const mostradas = Math.min(totalRvw, s.rvwShown || BASE);
      V.detHasMoreRvw = totalRvw > BASE;
      V.detMoreRvwTxt = mostradas >= totalRvw ? 'Ver menos reseñas' : 'Ver más reseñas';
      V.detMoreRvw = () => {
        const act = this.state.rvwShown || BASE;
        this.setState({ rvwShown: act >= totalRvw ? BASE : Math.min(totalRvw, act + PASO) });
      };
      V.detReviews = dd ? dd.reviews.slice(0, mostradas).map(rvwVM) : [
        { head: '5/5 Azy A', date: '11 nov. 2024', moreTxt: 'Ver menos', t: 'Este restaurante es excelente y cuenta con una atención excepcional hacia los comensales. Los platillos son deliciosos; probamos un tiradito de pescado que fue totalmente diferente a lo que había probado antes. El sabor del pescado era destacado y el jugo con aceite aromático con el que estaba bañado tenía un sabor delicioso. ¡Muy recomendable! Ordenamos varios platillos, y todo estuvo muy sabroso. En cuanto a los postres, nos sugirieron el rollo de canela con nieve, y la nieve tenía un auténtico sabor a vainilla, nada sintético. En resumen, un buen restaurante para cenar y disfrutar de un rato agradable.' },
        { head: '5/5 Itzel A', date: '21 may. 2025', moreTxt: 'Ver más', t: 'Muy buena tarde. Acudí el día de ayer al restaurante. Fue en sí misma una experiencia increíble, tenía toda la intención de conocer al Chef Benito, puesto que viajé desde el Estado de México para quizá conocerte, pero sobre todo para comer ahí. La única razón por la que no regresé el día de hoy a comer, fue quizá la poca empatía del personal sobre mi ferviente deseo de estar…' },
        { head: '5/5 Maria C', date: '19 jun. 2025', moreTxt: 'Ver más', t: 'Lugar bastante agradable, muy buen servicio, tienen menú de degustación o carta, variedad de cervezas. La comida es espectacular, sabores muy finos y naturales. Todo estuvo muy bueno en especial el estofado de cangrejo y el abulón. Todos los platillos estuvieron deliciosos. Los postres nada azucarados. Lugar 100% recomendado.' }
      ].map(rvwVM);
      V.detFotoSeed = 'rn-en';
    } else {
      V.detName = ''; V.detNum = ''; V.detPin = '#ccc'; V.detSeed = 'x'; V.detDesc = ''; V.detChips = [];
      V.detRating = ''; V.detRev = ''; V.detAddBg = '#F5B93F'; V.detAddCol = '#0E2A33'; V.detAddTxt = '';
      V.detAddClick = V.noop; V.detAskAI = V.noop; V.detAiChips = []; V.detDows = []; V.detTips = [];
      V.detRvwStars = null; V.detHisto = []; V.detReviews = [];
      V.detFotoSeed = 'x';
      V.hasDetAddress = false; V.detAddress = ''; V.hasDetHours = false; V.detHoursToday = '';
      V.hasDetDwell = false; V.hasDetMentions = false; V.hasDetPhone = false; V.detPhone = '';
      V.hasDetWebsite = false; V.detWebsite = ''; V.detOpenWeb = V.noop; V.detOpenG = V.noop; V.detOpenGm = V.noop;
      V.hasDetTips = false; V.detRvwAvg = ''; V.detRvwLabel = ''; V.detRvwCount = ''; V.hasDetHisto = false;
      V.detFoto1 = ''; V.detFoto2 = ''; V.detFoto3 = ''; V.detFoto = '';
      V.detHistoNota = ''; V.detHoursOpen = false; V.detHoursClosed = true; V.detHoursTxt = 'Mostrar horarios';
      V.detHoursToggle = V.noop; V.detWeekday = [];
      V.detHasMoreRvw = false; V.detMoreRvwTxt = 'Ver más reseñas'; V.detMoreRvw = V.noop;
    }

    // ── Variables de integración Ruta Nómada (plantilla parametrizada) ──
    // ── Nombre del viaje editable al hacer clic ──
    V.tripTitle = this.META.titulo;
    V.titleEditing = !!s.titleEdit;
    V.titleIdle = !s.titleEdit;
    V.titleOpen = () => {
      if (!this.puedeEditar) return;
      this.setState({ titleEdit: true });
      setTimeout(() => {
        const el = document.getElementById('rnTripTitle');
        if (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); }
      }, 0);
    };
    V.titleChange = (e) => {
      const v = e.target.value;
      this.META.titulo = v;
      this.setState({ _t: (s._t || 0) + 1 });
      clearTimeout(this._titT);
      if (v.trim()) this._titT = setTimeout(() => this._sync('plan_update.php', { nombre: v.trim() }), 700);
    };
    V.titleKey = (e) => { if (e.key === 'Enter' || e.key === 'Escape') e.target.blur(); };
    V.titleBlur = () => {
      clearTimeout(this._titT);
      const v = (this.META.titulo || '').trim();
      if (!v) { this.META.titulo = 'Viaje sin nombre'; }
      this.setState({ titleEdit: false });
      this._sync('plan_update.php', { nombre: this.META.titulo });
      document.title = this.META.titulo + ' — Ruta Nómada';
    };
    V.tripDates = this.META.fechas;
    V.destino = this.META.destino;
    V.destinoDesc = s.destinoDesc || '';
    V.hasDestinoDesc = !!(s.destinoDesc || '').trim();
    V.heroImg = this.META.hero;
    V.userInitial = this.USER.inicial;
    V.chatTitle = this.META.titulo;
    V.conceptoPlaceholder = 'Concepto (p. ej. Boletos ' + this.META.destino + ')';
    V.exSearchPh = this.META.destino;
    V.miembrosVM = this.MIEMBROS.map(m => ({
      inicial: m.inicial,
      titulo: m.nombre + ' · ' + m.rol,
      hasFoto: !!m.foto, foto: m.foto || ''
    }));
    return V;
  }
}
