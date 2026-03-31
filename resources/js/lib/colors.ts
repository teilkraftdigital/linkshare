export type Color = {
    name: string;
    color: string;
    bg: string;
};

export const COLORS: Color[] = [
    { name: 'gray', color: 'bg-gray-400', bg: 'bg-gray-400/20' },
    { name: 'red', color: 'bg-red-500', bg: 'bg-red-500/20' },
    { name: 'orange', color: 'bg-orange-500', bg: 'bg-orange-500/20' },
    { name: 'amber', color: 'bg-amber-500', bg: 'bg-amber-500/20' },
    { name: 'yellow', color: 'bg-yellow-400', bg: 'bg-yellow-400/20' },
    { name: 'lime', color: 'bg-lime-500', bg: 'bg-lime-500/20' },
    { name: 'green', color: 'bg-green-500', bg: 'bg-green-500/20' },
    { name: 'teal', color: 'bg-teal-500', bg: 'bg-teal-500/20' },
    { name: 'cyan', color: 'bg-cyan-500', bg: 'bg-cyan-500/20' },
    { name: 'blue', color: 'bg-blue-500', bg: 'bg-blue-500/20' },
    { name: 'indigo', color: 'bg-indigo-500', bg: 'bg-indigo-500/20' },
    { name: 'violet', color: 'bg-violet-500', bg: 'bg-violet-500/20' },
];

export const COLOR_BG: Record<string, string> = Object.fromEntries(
    COLORS.map((c) => [c.name, c.color]),
);

export const COLOR_BG_OPACITY: Record<string, string> = Object.fromEntries(
    COLORS.map((c) => [c.name, c.bg]),
);

export const HAS_COLOR = (color: string) =>
    COLORS.some((c) => c.name === color);
