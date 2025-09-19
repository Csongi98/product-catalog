export function toFt(v) {
    if (v == null) return "";
    return new Intl.NumberFormat("hu-HU").format(Math.round(v)) + " Ft";
}
