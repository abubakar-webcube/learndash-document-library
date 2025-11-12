// ViewModeSwitcher.jsx
// mode: "grid" | "list" | "folder"
export function ViewModeSwitcher({ mode, onChange }) {
  const baseBtn =
    "flex items-center gap-1 rounded-md p-[6px] text-xs md:text-sm transition cursor-pointer";

  const active =
    "bg-white text-black border border-[#dfdfdf]";
  const inactive =
    "text-gray-500 bg-[transparent] border border-[transparent]";

  const wrapper =
    "inline-flex";

  return (
    <div className={wrapper}>
      <button
        type="button"
        onClick={() => onChange("grid")}
        className={`${baseBtn} ${mode === "grid" ? active : inactive}`}
      >
        {/* grid icon */}
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="h-4 w-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
        >
          <rect x="3" y="3" width="7" height="7" rx="1" />
          <rect x="14" y="3" width="7" height="7" rx="1" />
          <rect x="3" y="14" width="7" height="7" rx="1" />
          <rect x="14" y="14" width="7" height="7" rx="1" />
        </svg>
        {/* <span className="hidden md:inline">Grid</span> */}
      </button>

      <button
        type="button"
        onClick={() => onChange("list")}
        className={`${baseBtn} ${mode === "list" ? active : inactive}`}
      >
        {/* list icon */}
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="h-4 w-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
        >
          <path d="M9 6h12" />
          <path d="M9 12h12" />
          <path d="M9 18h12" />
          <circle cx="4" cy="6" r="1.5" />
          <circle cx="4" cy="12" r="1.5" />
          <circle cx="4" cy="18" r="1.5" />
        </svg>
        {/* <span className="hidden md:inline">List</span> */}
      </button>

      <button
        type="button"
        onClick={() => onChange("folder")}
        className={`${baseBtn} ${mode === "folder" ? active : inactive}`}
      >
        {/* folder icon */}
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="h-4 w-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M3 7a2 2 0 0 1 2-2h4.2a2 2 0 0 1 1.4.58L12.8 7H19a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"
          />
        </svg>
        {/* <span className="hidden md:inline">Folder</span> */}
      </button>
    </div>
  );
}
