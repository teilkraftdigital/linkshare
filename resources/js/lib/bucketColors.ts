export type BucketColor = {
    name: string;
    bg: string;
};

export const BUCKET_COLORS: BucketColor[] = [
    { name: 'gray', bg: 'bg-gray-400' },
    { name: 'red', bg: 'bg-red-500' },
    { name: 'orange', bg: 'bg-orange-500' },
    { name: 'amber', bg: 'bg-amber-500' },
    { name: 'yellow', bg: 'bg-yellow-400' },
    { name: 'lime', bg: 'bg-lime-500' },
    { name: 'green', bg: 'bg-green-500' },
    { name: 'teal', bg: 'bg-teal-500' },
    { name: 'cyan', bg: 'bg-cyan-500' },
    { name: 'blue', bg: 'bg-blue-500' },
    { name: 'indigo', bg: 'bg-indigo-500' },
    { name: 'violet', bg: 'bg-violet-500' },
];

export const BUCKET_COLOR_BG: Record<string, string> = Object.fromEntries(
    BUCKET_COLORS.map((c) => [c.name, c.bg]),
);
