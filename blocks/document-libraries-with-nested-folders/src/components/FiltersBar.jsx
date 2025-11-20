// FiltersBar.jsx (example usage – optional)
import { useState } from "react";
import { SearchField } from "./SearchField";
import { ShowCountSelect } from "./ShowCountSelect";
import { ViewModeSwitcher } from "./ViewModeSwitcher";

export function FiltersBar() {
  const [search, setSearch] = useState("");
  const [perPage, setPerPage] = useState(10);
  const [view, setView] = useState("grid"); // "grid" | "list" | "folder"

  return (
    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <SearchField value={search} onChange={setSearch} />

      <div className="flex items-center gap-4">
        <ShowCountSelect value={perPage} onChange={setPerPage} />
        <ViewModeSwitcher mode={view} onChange={setView} />
      </div>
    </div>
  );
}
