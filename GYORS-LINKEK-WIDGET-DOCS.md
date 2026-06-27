# AB – Gyors linkek widget (Elementor)

> Forgalomszerző CTA-szekció a főoldalon: nagy, kattintható gombok/csempék a SEO
> landing oldalakra („Balatoni programok ma", „Hétvégi programok" stb.).
> A landing oldalakat a másik plugin adja: lásd `ab-esemenyek/PROGRAMOK-SZEKCIO-DOCS.md`.

Fájl: `includes/class-ab-quick-links-widget.php` · CSS: `assets/css/ab-widgets.css`
(„6. GYORS LINKEK" szekció) · Elementor widget neve: **AB – Gyors linkek** (`ab_quick_links`).

## Miért így

- A gombok **valódi `<a href>` linkek** → a Google bejárja, belső linkként SEO-erőt
  adnak át a landing oldalaknak. (Ezért NEM JS-generált a navigáció.)
- Brand defaultok: navy `#1B2D3F` outline → narancs `#E8943A` hover. Minden felülírható.

## Opciók (Elementor)

**Tartalom fül:**
- **Elrendezés**: `Csempe` (ikon felül + cím + alszöveg) vagy `Gombsor` (egy sorban).
  ⚠️ Az „Oszlopok száma" kontroll CSAK csempe módban látszik; a gombsor szélesség
  szerint tördel (vagy „Teljes szélességű gombok mobilon").
- Kis felirat + cím (opcionális).
- **Gombok (repeater)**: felirat, alszöveg, link (URL), **ikon** (Font Awesome pack
  vagy egyéni SVG – `Controls_Manager::ICONS`), és opcionális **per-gomb egyedi szín**
  (háttér/szöveg/keret, normál+hover; üres = a globális stílus).

**Stílus fül:**
- Elrendezés: reszponzív **oszlopszám** (csempe), térköz, igazítás (gombsor), teljes
  szélesség mobilon (gombsor), **hover-animáció** (emelés/nagyítás/nyíl).
- Fejléc: felirat + cím színe/tipográfiája/igazítása.
- Gomb/Csempe: padding, lekerekítés, ikon–szöveg távolság, keret, árnyék, és
  **háttér+szöveg Normál/Hover tabokkal**.
- Ikon: méret, önálló szín.
- Szöveg: felirat- és alszöveg-tipográfia, alszöveg szín.

## Technikai megjegyzések (továbbfejlesztéshez)

- A stílust az Elementor kontrollok adják `selectors`-on át; a CSS csak a **szerkezetet**
  + a két layoutot (`.ab-quicklinks--row` / `--tile`) + a hover-animációkat tartalmazza,
  brand fallbackkel. A control-defaultok adják az alap kinézetet.
- Per-gomb szín: a render minden `<a>`-ra kiír `elementor-repeater-item-{_id}` osztályt,
  a repeater color-kontrollok `{{WRAPPER}} .ab-quicklink{{CURRENT_ITEM}}` selectorral
  (nagyobb specificitás → felülírja a globálist).
- Ikon: `Icons_Manager::render_icon()` (FA-t automatikusan betölti). Régi (v1) emoji
  mező megszűnt → meglévő widgetnél egyszer újra ki kell választani az ikont.
- DM Sans (felirat) / Cormorant Garamond (cím) – a plugin többi widgetjével egyező.

## Üzemeltetés / buktatók

- **LiteSpeed JS-késleltetés**: a plugin fő fájlja kódból kizárja az Elementor/CF7/
  wp-i18n szkripteket a `litespeed_optm_js_defer_exc` szűrővel (különben
  `... is not defined` → fehér oldal). Ha új plugin JS-e törik így, bővítsd a listát.
- Verzió **2 helyen** bumpolandó: `ab-aktivbalaton-widgets.php` docblock `Version:` +
  `define('AB_WIDGETS_VERSION', ...)`. Deploy után LiteSpeed → Összes ürítése.
