// src/App.jsx
import { useEffect, useMemo, useState } from "react";
import { SearchField } from "./components/SearchField";
import { TagSelect } from "./components/TagSelect";
import { ShowCountSelect } from "./components/ShowCountSelect";
import { ViewModeSwitcher } from "./components/ViewModeSwitcher";
import { Breadcrumbs } from "./components/Breadcrumbs";
import { Pagination } from "./components/Pagination";
import FilePreviewModal from "./components/FilePreviewModal";

const DEFAULT_LAYOUT = "list";
const DEFAULT_LIMIT = 9;

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
  const av = (val) => (typeof val === "string" ? val.toLowerCase() : val);
  if (field === "reference") return (av(a.reference) ?? a.id) < (av(b.reference) ?? b.id) ? -1 * dir : (av(a.reference) ?? a.id) > (av(b.reference) ?? b.id) ? 1 * dir : 0;
  if (field === "title") return av(a.title) < av(b.title) ? -1 * dir : av(a.title) > av(b.title) ? 1 * dir : 0;
  if (field === "published") return (new Date(a.published || a.lastModified) - new Date(b.published || b.lastModified)) * dir;
  if (field === "modified") return (new Date(a.lastModified) - new Date(b.lastModified)) * dir;
  if (field === "author") return av(a.author) < av(b.author) ? -1 * dir : av(a.author) > av(b.author) ? 1 * dir : 0;
  return 0;
}
function App({
  initialView = DEFAULT_LAYOUT,
  initialPerPage = DEFAULT_LIMIT,
  enableSearch = true,
  restUrl = "",
  restNonce = "",
  currentUserId = null,
  libraries = [],
  categories = [],
  exclude = [],
  previewDocuments = [],
  initialFolders = [],
  visibleColumns = [],
}) {
  const [previewFile, setPreviewFile] = useState(null);
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [perPage, setPerPage] = useState(initialPerPage || DEFAULT_LIMIT);
  const [currentPage, setCurrentPage] = useState(1);
  const [view, setView] = useState(initialView || DEFAULT_LAYOUT);
  const [lastNonFavoritesView, setLastNonFavoritesView] = useState(initialView || DEFAULT_LAYOUT);
  const [folderStack, setFolderStack] = useState([]); // For drill-down
  const [sortField, setSortField] = useState("modified");
  const [sortDirection, setSortDirection] = useState("desc");
  const [documents, setDocuments] = useState([]);
  const [folders, setFolders] = useState([]);
  const [tags, setTags] = useState([]);
  const [selectedTags, setSelectedTags] = useState([]);
  const [loadingFolders, setLoadingFolders] = useState(false);
  const [loadingDocuments, setLoadingDocuments] = useState(false);
  const [error, setError] = useState("");
  const [favoriteIds, setFavoriteIds] = useState(new Set());
  const columns = useMemo(
    () =>
      Array.isArray(visibleColumns) && visibleColumns.length
        ? visibleColumns
        : ["image", "reference", "title", "published", "modified", "author", "favorites", "downloads", "download"],
    [visibleColumns]
  );

  const normalizedLibraries = useMemo(() => (Array.isArray(libraries) ? libraries : []), [libraries]);
  const normalizedCategories = useMemo(() => (Array.isArray(categories) ? categories : []), [categories]);
  const normalizedExclude = useMemo(() => (Array.isArray(exclude) ? exclude : []), [exclude]);

  const currentFolderId = folderStack.length ? folderStack[folderStack.length - 1] : null;
  const isFavoritesView = view === "favorites";
  const shouldShowDocuments = isFavoritesView || Boolean(currentFolderId);
  const apiBase = useMemo(() => {
    if (!restUrl) return "";
    try {
      const urlObj = new URL(restUrl, window.location.href);
      if (window.location.protocol === "https:" && urlObj.protocol === "http:") {
        urlObj.protocol = "https:";
      }
      return urlObj.toString().replace(/\/$/, "");
    } catch (e) {
      return restUrl.replace(/\/$/, "");
    }
  }, [restUrl]);
  const apiHeaders = useMemo(() => (restNonce ? { "X-WP-Nonce": restNonce } : {}), [restNonce]);

  const getTopLevelFolders = () => folders.filter((f) => f.parentId === null);
  const getChildrenOf = (parentId) => folders.filter((f) => f.parentId === parentId);
  // Fetch folders once (or when API props change)
  useEffect(() => {
    if (!apiBase) {
      setError("API base URL is missing.");
      setLoadingFolders(false);
      return;
    }
    let cancelled = false;
    setLoadingFolders(true);
    setError("");

    const folderParams = new URLSearchParams();
    if (normalizedLibraries.length) folderParams.append("libraries", normalizedLibraries.join(","));
    if (normalizedCategories.length) folderParams.append("categories", normalizedCategories.join(","));
    if (normalizedExclude.length) folderParams.append("exclude", normalizedExclude.join(","));

    const foldersUrl = folderParams.toString() ? `${apiBase}/folders?${folderParams.toString()}` : `${apiBase}/folders`;
    // Debug: log request
    // eslint-disable-next-line no-console
    console.debug("LDL fetch folders", { url: foldersUrl, headers: apiHeaders });

    fetch(foldersUrl, { headers: apiHeaders })
      .then((res) => (res.ok ? res.json() : Promise.reject(res)))
      .then((data) => {
        if (cancelled) return;
        // eslint-disable-next-line no-console
        console.debug("LDL folders response", { count: Array.isArray(data) ? data.length : 0, data });
        setFolders(Array.isArray(data) ? data : []);
      })
      .catch((err) => {
        if (cancelled) return;
        console.error("LDL: unable to load folders", err);
        setError("Unable to load folders.");
      })
      .finally(() => {
        if (!cancelled) setLoadingFolders(false);
      });

    return () => {
      cancelled = true;
    };
  }, [apiBase, apiHeaders]);
  // Fetch tags once (or when API props change)
  useEffect(() => {
    if (!apiBase) return;
    let cancelled = false;
    fetch(`${apiBase}/tags`, { headers: apiHeaders })
      .then((res) => (res.ok ? res.json() : Promise.reject(res)))
      .then((data) => { if (!cancelled) setTags(Array.isArray(data) ? data : []); })
      .catch(() => {});
    return () => { cancelled = true; };
  }, [apiBase, apiHeaders]);


  // Fetch documents when folder changes (or API props change)
  useEffect(() => {
    let cancelled = false;
    setLoadingDocuments(true);
    setError("");

    // If no API configured, stop
    if (!apiBase) {
      setLoadingDocuments(false);
      setError("API base URL is missing.");
      return undefined;
    }

    const params = new URLSearchParams();
    if (currentFolderId) params.append("folder", currentFolderId);
    if (normalizedLibraries.length) params.append("libraries", normalizedLibraries.join(","));
    if (normalizedCategories.length) params.append("categories", normalizedCategories.join(","));
    if (normalizedExclude.length) params.append("exclude", normalizedExclude.join(","));
    if (selectedTags.length) params.append("tags", selectedTags.join(","));
    const url = params.toString() ? `${apiBase}/documents?${params.toString()}` : `${apiBase}/documents`;
    // Debug: log request
    // eslint-disable-next-line no-console
    console.debug("LDL fetch documents", { url, headers: apiHeaders });

    fetch(url, { headers: apiHeaders })
      .then((res) => (res.ok ? res.json() : Promise.reject(res)))
      .then((data) => {
        if (cancelled) return;
        // eslint-disable-next-line no-console
        console.debug("LDL documents response", { count: Array.isArray(data) ? data.length : 0, data });
        setDocuments(Array.isArray(data) ? data : []);
        const favSet = new Set(
          Array.isArray(data) ? data.filter((d) => d.isFavorite).map((d) => d.id) : []
        );
        setFavoriteIds(favSet);
        setCurrentPage(1);
      })
      .catch((err) => {
        if (cancelled) return;
        console.error("LDL: unable to load documents", err);
        setError("Unable to load documents.");
      })
      .finally(() => {
        if (!cancelled) setLoadingDocuments(false);
      });

    return () => {
      cancelled = true;
    };
  }, [
    apiBase,
    apiHeaders,
    currentFolderId,
    normalizedLibraries,
    normalizedCategories,
    normalizedExclude,
    selectedTags,
  ]);

  const handleFavoriteToggle = async (docId) => {
    if (!apiBase || !restNonce || !currentUserId) return;
    const isFav = favoriteIds.has(docId);
    const body = { doc_id: docId, user_id: currentUserId };
    try {
      const res = await fetch(`${apiBase}/favorite`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": restNonce,
        },
        body: JSON.stringify(body),
      });
      if (!res.ok) throw new Error(`Favorite toggle failed: ${res.status}`);
      const data = await res.json();
      setFavoriteIds((prev) => {
        const next = new Set(prev);
        if (isFav) {
          next.delete(docId);
        } else {
          next.add(docId);
        }
        return next;
      });
      setDocuments((prev) =>
        prev.map((d) =>
          d.id === docId
            ? {
                ...d,
                isFavorite: !isFav,
                favorites: typeof d.favorites === "number" ? d.favorites + (isFav ? -1 : 1) : d.favorites,
              }
            : d
        )
      );
    } catch (err) {
      console.error(err);
    }
  };

  const getDocFolderIds = (doc) => {
    const raw = doc.folderIds ?? doc.folder_id ?? doc.folderId ?? doc.folder?.id;
    if (Array.isArray(raw)) return raw.map((v) => Number(v)).filter((v) => !Number.isNaN(v));
    if (typeof raw === "string") {
      return raw
        .split(",")
        .map((v) => Number(v.trim()))
        .filter((v) => !Number.isNaN(v));
    }
    const num = Number(raw);
    return Number.isNaN(num) ? [] : [num];
  };

  // folders with counts
  const foldersWithCounts = useMemo(
    () =>
      folders.map((folder) => ({
        ...folder,
        count:
          folder.count ??
          documents.filter((doc) => getDocFolderIds(doc).includes(Number(folder.id))).length,
      })),
    [folders, documents]
  );

  // folder name map
  const folderNameById = useMemo(
    () => Object.fromEntries(folders.map((f) => [Number(f.id), f.name])),
    [folders]
  );

  // Filtered documents
  const filteredDocuments = shouldShowDocuments
    ? documents.filter((doc) => {
        const isFav = favoriteIds.has(doc.id);
        const inViewScope = isFavoritesView
          ? isFav
          : currentFolderId
            ? getDocFolderIds(doc).includes(Number(currentFolderId))
            : false;
        if (!inViewScope) return false;
        if (!search) return true;
        return doc.title.toLowerCase().includes(search.toLowerCase());
      })
    : [];

  const sortedDocuments = [...filteredDocuments].sort((a, b) => sortDocuments(a, b, sortField, sortDirection));
  const paginatedDocuments = sortedDocuments.slice((currentPage - 1) * perPage, (currentPage - 1) * perPage + perPage);

  // Visible folders for FolderFilterRow
  const getVisibleFolders = () => {
    if (view === "favorites") return [];
    if (!currentFolderId) return getTopLevelFolders();
    const children = getChildrenOf(currentFolderId);
    return children.length ? children : [];
  };

  const toggleFavoritesView = () => {
    setView((prev) => {
      if (prev === "favorites") {
        return lastNonFavoritesView;
      }
      setLastNonFavoritesView(prev);
      return "favorites";
    });
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

  return (
    <main className="min-h-screen bg-gray-100 py-10">
      <div className="mx-auto max-w-6xl space-y-6 px-4">
        <header className="space-y-2">
          {/* <h1 className="text-2xl font-semibold text-gray-900 mb-[30px]">LearnDash Document Library Demo</h1> */}
          {/* <Breadcrumbs
            rootLabel="Document Library Demo"
            folders={folders}
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
          /> */}
          <Breadcrumbs
            rootLabel="LearnDash Document Library"
            folders={folders}
            selectedFolderId={currentFolderId}
            onFolderSelect={(id) => {
              if (!id) {
                setFolderStack([]); // clicked root
              } else {
                // Build the complete path to this folder
                let path = [];
                let current = folders.find(f => f.id === id);
                while (current) {
                  path.unshift(current.id);
                  current = folders.find(f => f.id === current.parentId);
                }
                setFolderStack(path);
              }
              setCurrentPage(1);
            }}
          />
        </header>

        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          {enableSearch && <SearchField value={searchInput} onChange={setSearchInput} onSearch={handleSearch} />}
          <TagSelect tags={tags} value={selectedTags} onChange={(next) => { setSelectedTags(next); setCurrentPage(1); }} />
          <div className="flex items-center gap-4">
            <ShowCountSelect value={perPage} onChange={setPerPage} />
            <ViewModeSwitcher
              mode={view}
              onChange={(next) => {
                if (next === "favorites") {
                  toggleFavoritesView();
                } else {
                  setView(next);
                }
              }}
            />
          </div>
        </div>

        {/* Folder pills */}
        <FolderFilterRow
          folders={getVisibleFolders().map((f) => ({
            ...f,
            count: foldersWithCounts.find((x) => x.id === f.id)?.count || 0,
          }))}
          selectedFolderId={currentFolderId}
          onSelect={handleFolderClick}
          disabled={isFavoritesView}
        />

        {shouldShowDocuments && (
          <>
            {(loadingFolders || loadingDocuments) && (
              <div className="text-sm text-gray-600">Loading...</div>
            )}
            {error && !loadingDocuments && !loadingFolders && (
              <div className="text-sm text-red-600">{error}</div>
            )}

            {/* Documents */}
            {view === "grid" ? (
              <>
                <DocumentGridView
                  documents={paginatedDocuments}
                  folderNameById={folderNameById}
                  openPreview={openPreview}
                  columns={columns}
                  favoriteIds={favoriteIds}
                  onFavoriteToggle={handleFavoriteToggle}
                  restBase={apiBase}
                  restNonce={restNonce}
                />
                <Pagination
                  totalItems={sortedDocuments.length}
                  perPage={perPage}
                  currentPage={currentPage}
                  onPageChange={setCurrentPage}
                />
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
                  columns={columns}
                  favoriteIds={favoriteIds}
                  onFavoriteToggle={handleFavoriteToggle}
                  restBase={apiBase}
                  restNonce={restNonce}
                />
                <Pagination
                  totalItems={sortedDocuments.length}
                  perPage={perPage}
                  currentPage={currentPage}
                  onPageChange={setCurrentPage}
                />
              </>
            )}
          </>
        )}

        {previewFile && <FilePreviewModal file={previewFile} onClose={closePreview} />}
      </div>
    </main>
  );
}

export default App;

// Folder pills
function FolderFilterRow({ folders, selectedFolderId, onSelect, disabled = false }) {
  return (
    <div className="flex flex-wrap gap-[14px] mb-4">
      {folders.map((folder) => {
        const isActive = folder.id === selectedFolderId;
        return (
          <button
            key={folder.id}
            type="button"
            onClick={() => {
              if (!disabled) onSelect(folder.id);
            }}
            disabled={disabled}
            className={`flex flex-1 flex-grow flex-[190px] min-w-[120px] max-w-[215px] items-center justify-between rounded-lg px-3 py-2 text-xs md:text-sm ${disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer"} ${isActive ? "bg-[#eceef0] text-gray-700 border border-blue-500" : "bg-[#eceef0] hover:bg-[#eeeeee] text-gray-700 border border-[#dfdfdf]"
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

// LIST / FOLDER view (design closer to screenshot)
function DocumentListView({
  documents,
  folderNameById,
  sortField,
  sortDirection,
  onSortChange,
  openPreview,
  columns,
  favoriteIds,
  onFavoriteToggle,
  restBase,
  restNonce,
}) {
  const colWidths = {
    image: "minmax(0,1.2fr)",
    reference: "minmax(0,1.2fr)",
    title: "minmax(0,2fr)",
    published: "minmax(0,1.2fr)",
    modified: "minmax(0,1.2fr)",
    author: "minmax(0,1fr)",
    favorites: "minmax(0,1fr)",
    downloads: "minmax(0,1fr)",
    download: "minmax(0,1fr)",
  };
  const columnsTemplate = columns.map((c) => colWidths[c] || "minmax(0,1fr)").join(" ");

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
          <div className="grid mb-[6px] items-center rounded-xl bg-gray-50 px-4 py-2 border border-[#dfdfdf]" style={{ gridTemplateColumns: columnsTemplate }}>
            {columns.includes("image") && <div className="text-xs font-medium text-gray-600">Image</div>}
            {columns.includes("reference") && <div><SortHeader label="Reference" fieldKey="reference" /></div>}
            {columns.includes("title") && <div><SortHeader label="Title" fieldKey="title" /></div>}
            {columns.includes("published") && <div><SortHeader label="Published" fieldKey="published" /></div>}
            {columns.includes("modified") && <div><SortHeader label="Last Modified" fieldKey="modified" /></div>}
            {columns.includes("author") && <div><SortHeader label="Author" fieldKey="author" /></div>}
            {columns.includes("favorites") && <div className="text-xs font-medium text-gray-600">Favorites</div>}
            {columns.includes("downloads") && <div className="text-xs font-medium text-gray-600">Downloads</div>}
            {columns.includes("download") && <div className="text-right text-xs font-medium text-gray-500">Download</div>}
          </div>

          {/* Rows */}
          <div className="space-y-2 pt-1">
            {documents.map((doc) => {
              const meta = getFileTypeMeta(doc.type);
              return (
                <div
                  key={doc.id}
                  className="grid items-center rounded-xl bg-white px-4 py-3 border border-[#dfdfdf] hover:border-blue-500"
                  style={{ gridTemplateColumns: columnsTemplate }}
                >
                  {columns.includes("image") && (
                    <div className="text-xs text-gray-700">
                      {doc.image ? <img src={doc.image} alt={doc.title} className="h-10 w-10 object-cover rounded" /> : (
                        <div className={`flex h-9 w-9 items-center justify-center rounded-lg ${meta.bg} ${meta.color} text-xs font-semibold`}>
                          {meta.label}
                        </div>
                      )}
                    </div>
                  )}
                  {columns.includes("reference") && <div className="text-xs text-gray-700">{doc.reference ?? doc.id}</div>}
                  {columns.includes("title") && (
                    <div className="flex items-center gap-3">
                      <div className="flex flex-col">
                        <a onClick={() => openPreview(doc)} className="cursor-pointer font-normal text-xs text-blue-600 hover:underline">
                          {doc.title}
                        </a>
                        <span className="text-xs text-gray-500">
                          {folderNameById[doc.folderId] || "Root"}
                        </span>
                      </div>
                    </div>
                  )}
                  {columns.includes("published") && <div className="text-xs text-gray-700">{formatDate(doc.published || doc.lastModified)}</div>}
                  {columns.includes("modified") && <div className="text-xs text-gray-700">{formatDate(doc.lastModified)}</div>}
                  {columns.includes("author") && <div className="text-xs text-gray-700">{doc.author || "Unknown"}</div>}
                  {columns.includes("favorites") && (
                    <div className="flex items-center gap-2 text-xs text-gray-700">
                      <button
                        type="button"
                        onClick={() => onFavoriteToggle(doc.id)}
                        className="cursor-pointer text-red-500 text-[20px] leading-none"
                        aria-label="Toggle favorite"
                      >
                        {favoriteIds.has(doc.id) ? "\u2665" : "\u2661"}
                      </button>
                    </div>
                  )}
                  {columns.includes("downloads") && <div className="text-xs text-gray-700">{doc.downloads ?? 0}</div>}
                  {columns.includes("download") && (
                    <div className="flex justify-end">
                      <a
                        href={doc.url}
                        download={doc.title}
                        onClick={(e) => {
                          // increment download count via REST, then allow download
                          if (restBase) {
                            const headers = { "Content-Type": "application/json" };
                            if (restNonce) headers["X-WP-Nonce"] = restNonce;
                            fetch(`${restBase}/download`, {
                              method: "POST",
                              headers,
                              credentials: "include",
                              body: JSON.stringify({ doc_id: doc.id }),
                            }).catch((err) => console.error("LDL: download increment failed", err));
                          }
                        }}
                        className="inline-flex items-center justify-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 cursor-pointer transition"
                      >
                        Download
                      </a>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>
    </>
  );
}

// GRID view (card style) with same fields/actions as list
function DocumentGridView({ documents, folderNameById, openPreview, columns, favoriteIds, onFavoriteToggle, restBase, restNonce }) {
  return (
    <section className="space-y-3">
      <h2 className="text-sm font-semibold text-gray-700">
        Documents ({documents.length})
      </h2>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {documents.map((doc) => {
          const meta = getFileTypeMeta(doc.type);
          const folderLabel =
            folderNameById[Number(doc.folderId)] ||
            (Array.isArray(doc.folderIds) ? folderNameById[Number(doc.folderIds[0])] : undefined) ||
            "Root";
          return (
            <div
              key={doc.id}
              className="flex flex-col items-center rounded-2xl bg-white p-4 py-5 border border-[#dfdfdf] hover:border-blue-500"
            >
              {columns.includes("image") && (
                <div
                  className={`mb-[20px] mt-[10px] flex h-14 w-14 items-center justify-center rounded-2xl ${meta.bg} ${meta.color} text-xs font-semibold`}
                >
                  {meta.label}
                </div>
              )}
              {columns.includes("reference") && <div className="text-xs text-gray-700">{doc.reference ?? doc.id}</div>}
              {columns.includes("title") && (
                <div className="flex-1 space-y-1 text-sm text-center">
                  <a onClick={() => openPreview(doc)} className="font-medium text-gray-800 hover:text-blue-600 hover:underline text-center cursor-pointer">{doc.title}</a>
                  <p className="text-xs text-gray-500 text-center">
                    {folderLabel}
                  </p>
                </div>
              )}
              {columns.includes("published") && <div className="text-xs text-gray-700">{formatDate(doc.published || doc.lastModified)}</div>}
              {columns.includes("modified") && <div className="text-xs text-gray-700">{formatDate(doc.lastModified)}</div>}
              {columns.includes("author") && <div className="text-xs text-gray-700">{doc.author || "Unknown"}</div>}
              {columns.includes("favorites") && (
                <div className="flex items-center gap-2 text-xs text-gray-700">
                  <button
                    type="button"
                    onClick={() => onFavoriteToggle(doc.id)}
                    className="cursor-pointer text-red-500 text-[20px] leading-none"
                    aria-label="Toggle favorite"
                  >
                    {favoriteIds.has(doc.id) ? "\u2665" : "\u2661"}
                  </button>
                </div>
              )}
              {columns.includes("downloads") && <div className="text-xs text-gray-700">{doc.downloads ?? 0}</div>}
              {columns.includes("download") && (
                <a
                  href={doc.url} download={doc.title}
                  onClick={() => {
                    if (restBase) {
                      const headers = { "Content-Type": "application/json" };
                      if (restNonce) headers["X-WP-Nonce"] = restNonce;
                      fetch(`${restBase}/download`, {
                        method: "POST",
                        headers,
                        credentials: "include",
                        body: JSON.stringify({ doc_id: doc.id }),
                      }).catch((err) => console.error("LDL: download increment failed", err));
                    }
                  }}
                  className="mt-4 inline-flex items-center justify-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 text-center cursor-pointer transition">
                  Download
                </a>
              )}
            </div>
          );
        })}
      </div>
    </section>
  );
}
