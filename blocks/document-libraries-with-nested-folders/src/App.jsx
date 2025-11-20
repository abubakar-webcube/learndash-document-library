// src/App.jsx
import { useState } from "react";
import { SearchField } from "./components/SearchField";
import { ShowCountSelect } from "./components/ShowCountSelect";
import { ViewModeSwitcher } from "./components/ViewModeSwitcher";
import { Breadcrumbs } from "./components/Breadcrumbs";
import documents from "./data/documents.json";
import { Pagination } from "./components/Pagination";
import FilePreviewModal from "./components/FilePreviewModal";

// Folders data
const FOLDERS = [
  { id: 1, name: "Folder 1", parentId: null },
  { id: 101, name: "Folder A", parentId: 1 },
  { id: 102, name: "Folder B", parentId: 1 },
  { id: 103, name: "Folder A1", parentId: 101 },
  { id: 104, name: "Folder A2", parentId: 101 },
  { id: 2, name: "Folder 2", parentId: null },
  { id: 3, name: "Folder 3", parentId: null },
  { id: 4, name: "Folder 4", parentId: null },
  { id: 5, name: "Folder 5", parentId: null },
  { id: 6, name: "Folder 6", parentId: null },
  { id: 7, name: "Folder 7", parentId: null },
  { id: 8, name: "Folder 8", parentId: null },
  { id: 9, name: "Folder 9", parentId: null },
  { id: 10, name: "Folder 10", parentId: null },
];

// File type meta
const FILE_TYPE_META = {
  pdf: { label: "PDF", bg: "bg-red-100", color: "text-red-500" },
  docx: { label: "W", bg: "bg-blue-100", color: "text-blue-500" },
  mp4: { label: "MP4", bg: "bg-rose-100", color: "text-rose-500" },
  mp3: { label: "MP3", bg: "bg-purple-100", color: "text-purple-500" },
  xlsx: { label: "XLSX", bg: "bg-green-100", color: "text-green-500" },
  png: { label: "PNG", bg: "bg-gray-100", color: "text-gray-500" },
};

function getFileTypeMeta(type) {
  return FILE_TYPE_META[type] || { label: type?.toUpperCase() || "?", bg: "bg-gray-100", color: "text-gray-500" };
}

function sizeToBytes(sizeStr) {
  if (!sizeStr) return 0;
  const [numStr, unitRaw] = sizeStr.split(" ");
  const num = parseFloat(numStr);
  const unit = (unitRaw || "").toUpperCase();
  if (unit.startsWith("KB")) return num * 1024;
  if (unit.startsWith("MB")) return num * 1024 * 1024;
  if (unit.startsWith("GB")) return num * 1024 * 1024 * 1024;
  return num;
}

function formatDate(dateStr) {
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" });
}

function sortDocuments(a, b, field, direction) {
  const dir = direction === "asc" ? 1 : -1;
  if (field === "file") return a.title.toLowerCase() < b.title.toLowerCase() ? -1 * dir : a.title.toLowerCase() > b.title.toLowerCase() ? 1 * dir : 0;
  if (field === "size") return (sizeToBytes(a.size) - sizeToBytes(b.size)) * dir;
  if (field === "type") return (a.type || "").toLowerCase() < (b.type || "").toLowerCase() ? -1 * dir : (a.type || "").toLowerCase() > (b.type || "").toLowerCase() ? 1 * dir : 0;
  return (new Date(a.lastModified) - new Date(b.lastModified)) * dir;
}

// Folder helpers
const getTopLevelFolders = () => FOLDERS.filter((f) => f.parentId === null);
const getChildrenOf = (parentId) => FOLDERS.filter((f) => f.parentId === parentId);

function App() {
  const [previewFile, setPreviewFile] = useState(null);
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [perPage, setPerPage] = useState(10);
  const [currentPage, setCurrentPage] = useState(1);
  const [view, setView] = useState("list");
  const [folderStack, setFolderStack] = useState([]); // For drill-down
  const [sortField, setSortField] = useState("lastModified");
  const [sortDirection, setSortDirection] = useState("desc");

  const currentFolderId = folderStack.length ? folderStack[folderStack.length - 1] : null;

  // folders with counts
  const foldersWithCounts = FOLDERS.map((folder) => ({
    ...folder,
    count: documents.filter((doc) => doc.folderId === folder.id).length,
  }));

  // folder name map
  const folderNameById = Object.fromEntries(FOLDERS.map((f) => [f.id, f.name]));

  // Filtered documents
  const filteredDocuments = documents.filter((doc) => {
    if (currentFolderId && doc.folderId !== currentFolderId) return false;
    if (!search) return true;
    return doc.title.toLowerCase().includes(search.toLowerCase());
  });

  const sortedDocuments = [...filteredDocuments].sort((a, b) => sortDocuments(a, b, sortField, sortDirection));
  const paginatedDocuments = sortedDocuments.slice((currentPage - 1) * perPage, (currentPage - 1) * perPage + perPage);

  // Visible folders for FolderFilterRow
  const getVisibleFolders = () => {
    if (!currentFolderId) return getTopLevelFolders();
    const children = getChildrenOf(currentFolderId);
    return children.length ? children : [];
  };

  // Handlers
  const openPreview = (file) => setPreviewFile(file);
  const closePreview = () => setPreviewFile(null);
  const handleSearch = (term) => setSearch(term.trim());
  const handleSortChange = (field) => {
    setSortDirection((prev) => (field === sortField ? (prev === "asc" ? "desc" : "asc") : "asc"));
    setSortField(field);
  };

  const handleFolderClick = (folderId) => {
    const children = getChildrenOf(folderId);
    if (children.length > 0) {
      setFolderStack((prev) => [...prev, folderId]); // drill down
      setCurrentPage(1);
    } else {
      setFolderStack([folderId]); // select leaf folder
      setCurrentPage(1);
    }
  };

  const handleBreadcrumbClick = (index) => {
    setFolderStack((prev) => prev.slice(0, index + 1));
    setCurrentPage(1);
  };

  return (
    <main className="min-h-screen bg-gray-100 py-10">
      <div className="mx-auto max-w-6xl space-y-6 px-4">
        <header className="space-y-2">
          <h1 className="text-2xl font-semibold text-gray-900 mb-[30px]">FileBird Document Library Demo</h1>
          {/* <Breadcrumbs
            rootLabel="Document Library Demo"
            folders={FOLDERS}
            folderStack={folderStack}
            onBreadcrumbClick={handleBreadcrumbClick}
          /> */}
          <Breadcrumbs
            rootLabel="Document Library Demo"
            folders={FOLDERS}
            selectedFolderId={currentFolderId}
            onFolderSelect={(id) => {
              if (!id) {
                setFolderStack([]); // clicked root
              } else {
                // Drill down
                setFolderStack((prev) => {
                  const index = prev.indexOf(id);
                  if (index !== -1) {
                    return prev;
                  } else {
                    return [...prev, id];
                  }
                });
              }
              setCurrentPage(1);
            }}
          />
        </header>

        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <SearchField value={searchInput} onChange={setSearchInput} onSearch={handleSearch} />
          <div className="flex items-center gap-4">
            <ShowCountSelect value={perPage} onChange={setPerPage} />
            <ViewModeSwitcher mode={view} onChange={setView} />
          </div>
        </div>

        {/* Folder pills */}
        {view === "folder" || currentFolderId ? (
          <FolderFilterRow
            folders={getVisibleFolders().map((f) => ({
              ...f,
              count: foldersWithCounts.find((x) => x.id === f.id)?.count || 0,
            }))}
            selectedFolderId={currentFolderId}
            onSelect={handleFolderClick}
          />
        ) : null}

        {/* Documents */}
        {view === "grid" ? (
          <>
            <DocumentGridView documents={paginatedDocuments} folderNameById={folderNameById} openPreview={openPreview} />
            <Pagination totalItems={sortedDocuments.length} perPage={perPage} currentPage={currentPage} onPageChange={setCurrentPage} />
          </>
        ) : (
          <>
            <DocumentListView
              documents={paginatedDocuments}
              folderNameById={folderNameById}
              sortField={sortField}
              sortDirection={sortDirection}
              onSortChange={handleSortChange}
              openPreview={openPreview}
            />
            <Pagination totalItems={sortedDocuments.length} perPage={perPage} currentPage={currentPage} onPageChange={setCurrentPage} />
          </>
        )}

        {previewFile && <FilePreviewModal file={previewFile} onClose={closePreview} />}
      </div>
    </main>
  );
}

export default App;

// Folder pills
function FolderFilterRow({ folders, selectedFolderId, onSelect }) {
  return (
    <div className="flex flex-wrap gap-[14px] mb-4">
      {folders.map((folder) => {
        const isActive = folder.id === selectedFolderId;
        return (
          <button
            key={folder.id}
            type="button"
            onClick={() => onSelect(folder.id)}
            className={`flex flex-1 flex-grow flex-[190px] min-w-[120px] max-w-[215px] items-center justify-between rounded-lg px-3 py-2 text-xs cursor-pointer md:text-sm ${isActive ? "bg-[#eceef0] text-gray-700 border border-blue-500" : "bg-[#eceef0] hover:bg-[#eeeeee] text-gray-700 border border-[#dfdfdf]"
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
            <span className="ml-3 inline-flex h-6 min-w-[2rem] items-center justify-center rounded-full px-2 text-xs bg-gray-100 text-gray-600">
              {folder.count}
            </span>
          </button>
        );
      })}
    </div>
  );
}

// DocumentListView & DocumentGridView are unchanged from your code
// ... (keep your existing implementations)


// LIST / FOLDER view (design closer to screenshot)
function DocumentListView({
  documents,
  folderNameById,
  sortField,
  sortDirection,
  onSortChange,
  openPreview
}) {
  const columnsTemplate =
    "grid-cols-[minmax(0,3fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.5fr)_minmax(0,1fr)]";

  const SortHeader = ({ label, fieldKey, alignRight = false }) => {
    const active = sortField === fieldKey;
    const arrowClasses = `h-3 w-3 transition-transform ${sortDirection === "desc" ? "rotate-180" : ""
      }`;

    return (
      <button
        type="button"
        onClick={() => onSortChange(fieldKey)}
        className={`group inline-flex items-center gap-1 text-xs font-medium cursor-pointer ${active ? "text-gray-800" : "text-gray-500"
          } ${alignRight ? "justify-end w-full" : ""}`}
      >
        <span>{label}</span>
        <span
          className={`flex items-center ${active ? "opacity-100 text-gray-600" : "opacity-40 group-hover:opacity-80"
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
    <>
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
                      <a onClick={() => openPreview(doc)} className="cursor-pointer font-normal text-xs text-blue-600 hover:underline">
                        {doc.title}
                      </a>
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
                    <a href={doc.url} download={doc.title}
                      className="inline-flex items-center justify-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 cursor-pointer transition">
                      Download
                    </a>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </section>
    </>
  );
}

// GRID view (card style)
function DocumentGridView({ documents, folderNameById, openPreview }) {
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
              <div className="flex-1 space-y-1 text-sm text-center">
                <a onClick={() => openPreview(doc)} className="font-medium text-gray-800 hover:text-blue-600 hover:underline text-center cursor-pointer">{doc.title}</a>
                <p className="text-xs text-gray-500 text-center">
                  {doc.size} • {folderNameById[doc.folderId] || "Root"}
                </p>
              </div>
              <a
                href={doc.url} download={doc.title}
                className="mt-4 inline-flex items-center justify-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 text-center cursor-pointer transition">
                Download
              </a>
            </div>
          );
        })}
      </div>
    </section>
  );
}
