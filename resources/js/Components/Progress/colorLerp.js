// Линейная интерполяция между двумя hex-цветами по t (0..1) — общий
// хелпер для тематических индикаторов прогресса (дом/монета), оба красят
// один и тот же рисунок по мере роста сбора.
export function lerpColor(fromHex, toHex, t) {
    const from = parseInt(fromHex.slice(1), 16);
    const to = parseInt(toHex.slice(1), 16);

    const fr = (from >> 16) & 255, fg = (from >> 8) & 255, fb = from & 255;
    const tr = (to >> 16) & 255, tg = (to >> 8) & 255, tb = to & 255;

    const r = Math.round(fr + (tr - fr) * t);
    const g = Math.round(fg + (tg - fg) * t);
    const b = Math.round(fb + (tb - fb) * t);

    return `rgb(${r}, ${g}, ${b})`;
}
