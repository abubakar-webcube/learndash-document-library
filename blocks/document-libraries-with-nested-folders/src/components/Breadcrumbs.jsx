// // src/components/Breadcrumbs.jsx, Previous version

// export function Breadcrumbs({
//   rootLabel = "Document Library Demo",
//   currentLabel = "All documents",
// }) {
//   return (
//     <nav
//       className="flex items-center gap-2 text-sm text-gray-600"
//       aria-label="Breadcrumb"
//     >
//       {/* Small blue folder/document icon */}
//       <span className="inline-flex h-5 w-5 items-center justify-center rounded bg-blue-50 text-blue-600">
//         <svg
//           xmlns="http://www.w3.org/2000/svg"
//           className="h-3.5 w-3.5"
//           viewBox="0 0 24 24"
//           fill="none"
//           stroke="currentColor"
//           strokeWidth="2"
//         >
//           <path
//             strokeLinecap="round"
//             strokeLinejoin="round"
//             d="M3 7a2 2 0 0 1 2-2h4.2a2 2 0 0 1 1.4.58L12.8 7H19a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"
//           />
//         </svg>
//       </span>

//       <ol className="flex items-center gap-1">
//         <li className="font-medium text-gray-800">{rootLabel}</li>
//         <li className="text-gray-400">/</li>
//         <li className="text-gray-500">{currentLabel}</li>
//       </ol>
//     </nav>
//   );
// }























// src/components/Breadcrumbs.jsx
import React, { useState, useRef, useEffect } from "react";
import '../App.css'

export function Breadcrumbs({
  rootLabel = "Document Library Demo",
  folders = [],
  onFolderSelect,
  selectedFolderId,       // ← NEW PROP
}) {
  const [selectedFolderPath, setSelectedFolderPath] = useState([]);
  const [openDropdownIds, setOpenDropdownIds] = useState([]);
  const dropdownRef = useRef(null);

  useEffect(() => {
    function handleClickOutside(event) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setOpenDropdownIds([]);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const topLevelFolders = folders.filter(f => f.parentId === null);
  const getChildren = (parentId) => folders.filter(f => f.parentId === parentId);

  const handleFolderSelect = (folder) => {
    buildPath(folder.id);
    onFolderSelect(folder.id);
  };

  // 🔥 NEW: Rebuild path when selectedFolderId changes
  const buildPath = (folderId) => {
    if (!folderId) {
      setSelectedFolderPath([]);
      return;
    }
    let path = [];
    let current = folders.find(f => f.id === folderId);
    while (current) {
      path.unshift(current);
      current = folders.find(f => f.id === current.parentId);
    }
    setSelectedFolderPath(path);
  };

  useEffect(() => {
    buildPath(selectedFolderId);
  }, [selectedFolderId, folders]);

  const toggleDropdown = (folderId) => {
    setOpenDropdownIds(prev =>
      prev.includes(folderId)
        ? prev.filter(id => id !== folderId)
        : [...prev, folderId]
    );
  };

  const renderDropdownItem = (folder) => {
    const children = getChildren(folder.id);
    const isOpen = openDropdownIds.includes(folder.id);

    return (
      <li key={folder.id} className="relative group">
        <div className="flex justify-between items-center px-3 py-2 cursor-pointer font-normal text-xs hover:bg-blue-50">
          <span onClick={() => handleFolderSelect(folder)}>{folder.name}</span>
          {children.length > 0 && (
            <svg
              className={`w-3 h-3 ml-2 cursor-pointer transition-transform ${isOpen ? "rotate-180" : ""}`}
              fill="none"
              viewBox="0 0 14 14"
              stroke="currentColor"
              onClick={() => toggleDropdown(folder.id)}
            >
              <path d="M4 6L7 9L10 6" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          )}
        </div>

        {children.length > 0 && (
          <ul className={`absolute left-full top-0 mt-0 w-40 border border-[#eeeeee] bg-white z-50 rounded-[10px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all ${isOpen ? "opacity-100 visible" : ""}`}>
            {children.map(renderDropdownItem)}
          </ul>
        )}
      </li>
    );
  };

  return (
    <nav className="relative flex items-center gap-1 text-sm text-gray-600" ref={dropdownRef}>
      {/* Root breadcrumb */}
      <div className="relative lv1 font-medium text-gray-800 flex items-center gap-1" ref={dropdownRef}>
        <span
          onClick={() => { setSelectedFolderPath([]); onFolderSelect(null); }}
          className="cursor-pointer hover:text-blue-600"
        >
          {rootLabel}
        </span>
        <svg
          className="h-3 w-3 cursor-pointer"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          onClick={() => toggleDropdown("root")}
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>

        {openDropdownIds.includes("root") && (
          <ul className="absolute left-0 top-full mt-1 w-40 border border-[#eeeeee] bg-white z-50 rounded-[10px] shadow-md">
            {topLevelFolders.map(renderDropdownItem)}
          </ul>
        )}
      </div>

      {/* Breadcrumbs for selected path */}
      {selectedFolderPath.length > 0 && (
        <>
          /
          <ol className="flex lv2 items-center gap-1 mt-1">
            {selectedFolderPath.map((folder, idx) => {
              const children = getChildren(folder.id);
              const isOpen = openDropdownIds.includes(folder.id);

              return (
                <li key={folder.id} className="relative flex items-center gap-1 group">
                  <span
                    className="cursor-pointer hover:text-blue-600"
                    onClick={() => handleFolderSelect(folder)}
                  >
                    {folder.name}
                  </span>

                  {children.length > 0 && (
                    <>
                      <svg
                        className="h-3 w-3 cursor-pointer"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        onClick={() => toggleDropdown(folder.id)}
                      >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                      </svg>

                      <ul className={`absolute left-0 top-full mt-0 w-40 border border-[#eeeeee] bg-white z-50 rounded-[10px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all ${isOpen ? "opacity-100 visible" : ""}`}>
                        {children.map(renderDropdownItem)}
                      </ul>
                    </>
                  )}

                  {idx < selectedFolderPath.length - 1 && (
                    <span className="text-gray-400">/</span>
                  )}
                </li>
              );
            })}
          </ol>
        </>
      )}
    </nav>
  );
}
