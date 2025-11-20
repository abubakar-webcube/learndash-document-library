// src/App.jsx
import { useState } from "react";
import { SearchField } from "./components/SearchField";
import { ShowCountSelect } from "./components/ShowCountSelect";
import { ViewModeSwitcher } from "./components/ViewModeSwitcher";
import { Breadcrumbs } from "./components/Breadcrumbs";
import defaultDocuments from "./data/documents.json";

// Demo folders similar to the FileBird example
const DEFAULT_FOLDERS = [
  { id: 1, name: "Folder 1" },
  { id: 2, name: "Folder 2" },
  { id: 3, name: "Folder 3" },
  { id: 4, name: "Folder 4" },
  { id: 5, name: "Folder 5" },
  { id: 6, name: "Folder 6" },
  { id: 7, name: "Folder 7" },
  { id: 8, name: "Folder 8" },
  { id: 9, name: "Folder 9" },
  { id: 10, name: "Folder 10" }
];

// File-type badge + icon style mapping
const FILE_TYPE_META = {
  pdf: { label: "PDF", bg: "bg-red-100", color: "text-red-500" },
  docx: { label: "W", bg: "bg-blue-100", color: "text-blue-500" },
  mp4: { label: "MP4", bg: "bg-rose-100", color: "text-rose-500" },
  mp3: { label: "MP3", bg: "bg-purple-100", color: "text-purple-500" },
  xlsx: { label: "XLSX", bg: "bg-green-100", color: "text-green-500" },
  png: { label: "PNG", bg: "bg-gray-100", color: "text-gray-500" }
};

function getFileTypeMeta(type) {
  return FILE_TYPE_META[type] || {
    label: type?.toUpperCase() || "?",
    bg: "bg-gray-100",
    color: "text-gray-500"
  };
}

function sizeToBytes(sizeStr) {
  if (!sizeStr) return 0;
  const [numStr, unitRaw] = sizeStr.split(" ");
  const num = parseFloat(numStr);
  const unit = (unitRaw || "").toUpperCase();
  let mul = 1;
  if (unit.startsWith("KB")) mul = 1024;
  else if (unit.startsWith("MB")) mul = 1024 * 1024;
  else if (unit.startsWith("GB")) mul = 1024 * 1024 * 1024;
  return num * mul;
}

function formatDate(dateStr) {
  const d = new Date(dateStr);
  if (Number.isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString("en-US", {
    month: "short",
    day: "2-digit",
    year: "numeric"
  });
}

function sortDocuments(a, b, field, direction) {
  const dir = direction === "asc" ? 1 : -1;

  if (field === "file") {
    const av = a.title.toLowerCase();
    const bv = b.title.toLowerCase();
    return av < bv ? -1 * dir : av > bv ? 1 * dir : 0;
  }

  if (field === "size") {
    const av = sizeToBytes(a.size);
    const bv = sizeToBytes(b.size);
    return (av - bv) * dir;
  }

  if (field === "type") {
    const av = (a.type || "").toLowerCase();
    const bv = (b.type || "").toLowerCase();
    return av < bv ? -1 * dir : av > bv ? 1 * dir : 0;
  }

  // default: lastModified
  const av = new Date(a.lastModified).getTime();
  const bv = new Date(b.lastModified).getTime();
  return (av - bv) * dir;
}

function normalizeDocuments(documents) {
  if (Array.isArray(documents) && documents.length) {
    return documents;
  }
  return defaultDocuments;
}

function normalizeFolders(folders) {
  if (Array.isArray(folders) && folders.length) {
    return folders;
  }
  return DEFAULT_FOLDERS;
}

function App({
  documents,
  folders,
  initialView = "list",
  initialPerPage = 10,
  enableSearch = true,
  initialSearchTerm = "",
  className = "",
  showDebugState = false
}) {
  const documentList = normalizeDocuments(documents);
  const folderList = normalizeFolders(folders);

  const [searchInput, setSearchInput] = useState(initialSearchTerm);
  const [search, setSearch] = useState(initialSearchTerm.trim());
  const [perPage, setPerPage] = useState(initialPerPage);
  const [view, setView] = useState(initialView);
  const [selectedFolderId, setSelectedFolderId] = useState(null);

  const [sortField, setSortField] = useState("lastModified");
  const [sortDirection, setSortDirection] = useState("desc");

  const handleSearch = (term) => {
    setSearch(term.trim());
  };

  const handleSortChange = (field) => {
    setSortDirection((prevDir) =>
      field === sortField ? (prevDir === "asc" ? "desc" : "asc") : "asc"
    );
    setSortField(field);
  };

  const foldersWithCounts = folderList.map((folder) => ({
    ...folder,
    count: documentList.filter((doc) => doc.folderId === folder.id).length
  }));

  const folderNameById = Object.fromEntries(folderList.map((f) => [f.id, f.name]));
  const effectiveSearch = enableSearch ? search : "";

  const filteredDocuments = documentList.filter((doc) => {
    if (selectedFolderId && doc.folderId !== selectedFolderId) return false;
    if (!effectiveSearch) return true;
    return doc.title.toLowerCase().includes(effectiveSearch.toLowerCase());
  });

  const sortedDocuments = [...filteredDocuments].sort((a, b) =>
    sortDocuments(a, b, sortField, sortDirection)
  );

  const paginatedDocuments = perPage
    ? sortedDocuments.slice(0, perPage)
    : sortedDocuments;

  const currentFolderName = selectedFolderId
    ? folderNameById[selectedFolderId] || "All documents"
    : "All documents";

  const wrapperClasses = ["ldl-app bg-gray-100 py-10", className]
    .filter(Boolean)
    .join(" ");

  return (
    <div className={wrapperClasses}>
      <div className="mx-auto max-w-6xl space-y-6 px-4">
        {/* Page title + breadcrumbs */}
        <header className="space-y-2">
          <h1 className="text-2xl font-semibold text-gray-900 mb-[30px]">
            FileBird Document Library Demo
          </h1>
          <Breadcrumbs
            rootLabel="Document Library Demo"
            currentLabel={currentFolderName}
          />
        </header>

        {/* Filters row */}
        <div
          className={`flex flex-col gap-3 md:flex-row md:items-center ${
            enableSearch ? "md:justify-between" : "md:justify-end"
          }`}
        >
          {enableSearch && (
            <SearchField
              value={searchInput}
              onChange={setSearchInput}
              onSearch={handleSearch}
            />
          )}

          <div className="flex items-center gap-4">
            <ShowCountSelect value={perPage} onChange={setPerPage} />
            <ViewModeSwitcher mode={view} onChange={setView} />
          </div>
        </div>

        {/* Folder filter pills */}
        <FolderFilterRow
          folders={foldersWithCounts}
          selectedFolderId={selectedFolderId}
          onSelect={setSelectedFolderId}
        />

        {/* Debug box (optional) */}
        {showDebugState && (
          <section className="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-700">
            <p className="mb-2 font-medium text-gray-800">
              Current filter state (applied):
            </p>
            <pre className="overflow-x-auto rounded bg-gray-50 p-3 text-xs">
              {JSON.stringify(
                {
                  search: effectiveSearch,
                  selectedFolderId,
                  perPage,
                  view,
                  sortField,
                  sortDirection,
                  visibleDocuments: paginatedDocuments.length
                },
                null,
                2
              )}
            </pre>
          </section>
        )}

        {/* Main content area: GRID vs LIST/FOLDER */}
        {view === "grid" ? (
          <DocumentGridView
            documents={paginatedDocuments}
            folderNameById={folderNameById}
          />
        ) : (
          <DocumentListView
            documents={paginatedDocuments}
            folderNameById={folderNameById}
            sortField={sortField}
            sortDirection={sortDirection}
            onSortChange={handleSortChange}
          />
        )}
      </div>
    </div>
  );
}

export default App;

// -----------------------------------------------------------------------------
// Helper components
// -----------------------------------------------------------------------------

function FolderFilterRow({ folders, selectedFolderId, onSelect }) {
  return (
    <div className="flex flex-wrap gap-[14px]">
      {folders.map((folder) => {
        const isActive = folder.id === selectedFolderId;
        return (
          <button
            key={folder.id}
            type="button"
            onClick={() => onSelect(isActive ? null : folder.id)}
            className={`flex flex-1 flex-grow flex-[190px] min-w-[120px] items-center justify-between rounded-lg px-3 py-2 text-xs cursor-pointer md:text-sm ${
              isActive
                ? "bg-[#eceef0] text-gray-700 border border-blue-500"
                : "bg-[#eceef0] hover:bg-[#eeeeee] text-gray-700 border border-[#dfdfdf]"
            }`}
          >
            <span className="inline-flex items-center gap-2">
              <span className="inline-flex h-5 w-5 items-center justify-center rounded text-black">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"></path>
                </svg>
              </span>
              <span className="font-normal">{folder.name}</span>
            </span>
            <span
              className={`ml-3 inline-flex h-6 min-w-[2rem] items-center justify-center rounded-full px-2 text-xs ${
                isActive
                  ? "bg-gray-100 text-gray-600"
                  : "bg-gray-100 text-gray-600"
              }`}
            >
              {folder.count}
            </span>
          </button>
        );
      })}
    </div>
  );
}

function DocumentListView({
  documents,
  folderNameById,
  sortField,
  sortDirection,
  onSortChange
}) {
  const columnsTemplate =
    "grid-cols-[minmax(0,3fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.5fr)_minmax(0,1fr)]";

  const SortHeader = ({ label, fieldKey, alignRight = false }) => {
    const active = sortField === fieldKey;
    const arrowClasses = `h-3 w-3 transition-transform ${
      sortDirection === "desc" ? "rotate-180" : ""
    }`;

    return (
      <button
        type="button"
        onClick={() => onSortChange(fieldKey)}
        className={`group inline-flex items-center gap-1 text-xs font-medium ${
          active ? "text-gray-800" : "text-gray-500"
        } ${alignRight ? "justify-end w-full" : ""}`}
      >
        <span>{label}</span>
        <span
          className={`flex items-center ${
            active ? "opacity-100 text-gray-600" : "opacity-40 group-hover:opacity-80"
          }`}
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            className={arrowClasses}
          >
            <path d="M5.23 12.79a.75.75 0 001.06 0L10 9.06l3.71 3.73a.75.75 0 001.06-1.06l-4.24-4.25a.75.75 0 00-1.06 0L5.23 11.73a.75.75 0 000 1.06z" />
          </svg>
        </span>
      </button>
    );
  };

  return (
    <section className="space-y-3">
      <h2 className="text-sm font-semibold text-gray-700">
        Documents ({documents.length})
      </h2>

      <div className="">
        {/* Header row */}
        <div
          className={`grid ${columnsTemplate} mb-[6px] items-center rounded-xl bg-gray-50 px-4 py-2 border border-[#dfdfdf]`}
        >
          <div>
            <SortHeader label="File" fieldKey="file" />
          </div>
          <div>
            <SortHeader label="Size" fieldKey="size" />
          </div>
          <div>
            <SortHeader label="Type" fieldKey="type" />
          </div>
          <div>
            <SortHeader label="Last Modified" fieldKey="lastModified" />
          </div>
          <div className="text-right text-xs font-medium text-gray-500">
            Download
          </div>
        </div>

        {/* Rows */}
        <div className="space-y-2 pt-1">
          {documents.map((doc) => {
            const meta = getFileTypeMeta(doc.type);
            return (
              <div
                key={doc.id}
                className={`grid ${columnsTemplate} items-center rounded-xl bg-white px-4 py-3 border border-[#dfdfdf] hover:border-blue-500`}
              >
                {/* File column */}
                <div className="flex items-center gap-3">
                  <div
                    className={`flex h-9 w-9 items-center justify-center rounded-lg ${meta.bg} ${meta.color} text-xs font-semibold`}
                  >
                    {meta.label}
                  </div>
                  <div className="flex flex-col">
                    <span className="cursor-pointer font-normal text-xs text-blue-600 hover:underline">
                      {doc.title}
                    </span>
                    <span className="text-xs text-gray-500">
                      {folderNameById[doc.folderId] || "Root"}
                    </span>
                  </div>
                </div>

                {/* Size */}
                <div className="text-xs text-gray-700">{doc.size}</div>

                {/* Type */}
                <div className="text-xs uppercase text-gray-700">
                  {doc.type}
                </div>

                {/* Last modified */}
                <div className="text-xs text-gray-700">
                  {formatDate(doc.lastModified)}
                </div>

                {/* Download button */}
                <div className="flex justify-end">
                  <button className="inline-flex items-center justify-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 cursor-pointer transition">
                    Download
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function DocumentGridView({ documents, folderNameById }) {
  return (
    <section className="space-y-3">
      <h2 className="text-sm font-semibold text-gray-700">
        Documents ({documents.length})
      </h2>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {documents.map((doc) => {
          const meta = getFileTypeMeta(doc.type);
          return (
            <div
              key={doc.id}
              className="flex flex-col items-center rounded-2xl bg-white p-4 py-5 border border-[#dfdfdf] hover:border-blue-500"
            >
              <div
                className={`mb-[40px] mt-[40px] flex h-14 w-14 items-center justify-center rounded-2xl ${meta.bg} ${meta.color} text-xs font-semibold`}
              >
                {meta.label}
              </div>
              <div className="flex-1 space-y-1 text-sm">
                <p className="font-medium text-gray-800 text-center">{doc.title}</p>
                <p className="text-xs text-gray-500 text-center">
                  {doc.size} • {folderNameById[doc.folderId] || "Root"}
                </p>
              </div>
              <button className="mt-4 inline-flex items-center justify-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 text-center cursor-pointer transition">
                Download
              </button>
            </div>
          );
        })}
      </div>
    </section>
  );
}
