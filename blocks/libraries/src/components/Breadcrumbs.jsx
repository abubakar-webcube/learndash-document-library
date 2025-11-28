// // src/components/Breadcrumbs.jsx
// import React, { useState, useRef, useEffect } from "react";
// import '../App.css'

// export function Breadcrumbs({
//   rootLabel = "LearnDash Document Library",
//   folders = [],
//   onFolderSelect,
//   selectedFolderId,       // ← NEW PROP
// }) {
//   const [selectedFolderPath, setSelectedFolderPath] = useState([]);
//   const [openDropdownIds, setOpenDropdownIds] = useState([]);
//   const dropdownRef = useRef(null);

//   useEffect(() => {
//     function handleClickOutside(event) {
//       if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
//         setOpenDropdownIds([]);
//       }
//     }
//     document.addEventListener("mousedown", handleClickOutside);
//     return () => document.removeEventListener("mousedown", handleClickOutside);
//   }, []);

//   const topLevelFolders = folders.filter(f => f.parentId === null);
//   const getChildren = (parentId) => folders.filter(f => f.parentId === parentId);

//   const handleFolderSelect = (folder) => {
//     buildPath(folder.id);
//     onFolderSelect(folder.id);
//     setOpenDropdownIds([]); // Close all dropdowns after selection
//   };

//   // 🔥 NEW: Rebuild path when selectedFolderId changes
//   const buildPath = (folderId) => {
//     if (!folderId) {
//       setSelectedFolderPath([]);
//       return;
//     }
//     let path = [];
//     let current = folders.find(f => f.id === folderId);
//     while (current) {
//       path.unshift(current);
//       current = folders.find(f => f.id === current.parentId);
//     }
//     setSelectedFolderPath(path);
//   };

//   useEffect(() => {
//     buildPath(selectedFolderId);
//   }, [selectedFolderId, folders]);

//   const toggleDropdown = (folderId) => {
//     setOpenDropdownIds(prev =>
//       prev.includes(folderId)
//         ? prev.filter(id => id !== folderId)
//         : [...prev, folderId]
//     );
//   };

//   const renderDropdownItem = (folder) => {
//     const children = getChildren(folder.id);
//     const isOpen = openDropdownIds.includes(folder.id);

//     return (
//       <li key={folder.id} className="relative group">
//         <div className="flex justify-between items-center px-3 py-2 cursor-pointer font-normal text-xs hover:bg-blue-50">
//           <span onClick={() => handleFolderSelect(folder)}>{folder.name}</span>
//           {children.length > 0 && (
//             <svg
//               className={`w-3 h-3 ml-2 cursor-pointer transition-transform ${isOpen ? "rotate-180" : ""}`}
//               fill="none"
//               viewBox="0 0 14 14"
//               stroke="currentColor"
//               onClick={() => toggleDropdown(folder.id)}
//             >
//               <path d="M4 6L7 9L10 6" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round" />
//             </svg>
//           )}
//         </div>

//         {children.length > 0 && (
//           <ul className={`absolute left-full top-0 mt-0 w-40 border border-[#eeeeee] bg-white z-50 rounded-[10px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all ${isOpen ? "opacity-100 visible" : ""}`}>
//             {children.map(renderDropdownItem)}
//           </ul>
//         )}
//       </li>
//     );
//   };

//   return (
//     <nav className="relative flex items-center gap-1 text-sm text-gray-600" ref={dropdownRef}>
//       {/* Root breadcrumb */}
//       <div className="relative lv1 font-medium text-gray-800 flex items-center gap-1" ref={dropdownRef}>
//         <span
//           onClick={() => { setSelectedFolderPath([]); onFolderSelect(null); }}
//           className="cursor-pointer hover:text-blue-600"
//         >
//           {rootLabel}
//         </span>
//         <svg
//           className="h-3 w-3 cursor-pointer"
//           fill="none"
//           viewBox="0 0 24 24"
//           stroke="currentColor"
//           onClick={() => toggleDropdown("root")}
//         >
//           <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
//         </svg>

//         {openDropdownIds.includes("root") && (
//           <ul className="absolute left-0 top-full mt-1 w-40 border border-[#eeeeee] bg-white z-50 rounded-[10px] shadow-md">
//             {topLevelFolders.map(renderDropdownItem)}
//           </ul>
//         )}
//       </div>

//       {/* Breadcrumbs for selected path */}
//       {selectedFolderPath.length > 0 && (
//         <>
//           /
//           <ol className="flex lv2 items-center gap-1 mt-1">
//             {selectedFolderPath.map((folder, idx) => {
//               const children = getChildren(folder.id);
//               const isOpen = openDropdownIds.includes(folder.id);

//               return (
//                 <li key={folder.id} className="relative flex items-center gap-1 group">
//                   <span
//                     className="cursor-pointer hover:text-blue-600"
//                     onClick={() => handleFolderSelect(folder)}
//                   >
//                     {folder.name}
//                   </span>

//                   {children.length > 0 && (
//                     <>
//                       <svg
//                         className="h-3 w-3 cursor-pointer"
//                         fill="none"
//                         viewBox="0 0 24 24"
//                         stroke="currentColor"
//                         onClick={() => toggleDropdown(folder.id)}
//                       >
//                         <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
//                       </svg>

//                       <ul className={`absolute left-0 top-full mt-0 w-40 border border-[#eeeeee] bg-white z-50 rounded-[10px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all ${isOpen ? "opacity-100 visible" : ""}`}>
//                         {children.map(renderDropdownItem)}
//                       </ul>
//                     </>
//                   )}

//                   {idx < selectedFolderPath.length - 1 && (
//                     <span className="text-gray-400">/</span>
//                   )}
//                 </li>
//               );
//             })}
//           </ol>
//         </>
//       )}
//     </nav>
//   );
// }



// src/components/Breadcrumbs.jsx
import React, { useState, useRef, useEffect } from "react";
import '../App.css'

export function Breadcrumbs({
  rootLabel = "LearnDash Document Library",
  folders = [],
  onFolderSelect,
  selectedFolderId,
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
    setOpenDropdownIds([]);
  };

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

  const handleGoBack = () => {
    if (selectedFolderPath.length === 0) return;
    
    if (selectedFolderPath.length === 1) {
      // If only one folder in path, go back to root
      setSelectedFolderPath([]);
      onFolderSelect(null);
    } else {
      // Go to the parent folder (one step back)
      const parentFolder = selectedFolderPath[selectedFolderPath.length - 2];
      handleFolderSelect(parentFolder);
    }
  };

  const isAtRoot = selectedFolderPath.length === 0;

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
    <nav className="relative flex items-center gap-2 text-sm text-gray-600" ref={dropdownRef}>
      {/* Back Button */}
      <button
        onClick={handleGoBack}
        disabled={isAtRoot}
        className={`flex items-center justify-center w-7 h-7 rounded-md transition ${
          isAtRoot
            ? "text-gray-300 cursor-not-allowed bg-gray-50"
            : "text-gray-600 hover:bg-gray-100 hover:text-gray-900 cursor-pointer"
        }`}
        aria-label="Go back"
        title={isAtRoot ? "Already at root" : "Go back"}
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          width="18"
          height="18"
          fill="currentColor"
        >
          <path d="M10.8284 12.0007L15.7782 16.9504L14.364 18.3646L8 12.0007L14.364 5.63672L15.7782 7.05093L10.8284 12.0007Z" />
        </svg>
      </button>

      {/* Root breadcrumb */}
      <div className="relative lv1 font-medium text-gray-800 flex items-center gap-1">
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