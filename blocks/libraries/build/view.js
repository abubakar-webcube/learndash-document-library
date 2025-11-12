/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/LibrariesReactViews.js":
/*!************************************!*\
  !*** ./src/LibrariesReactViews.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);


const VIEW_GRID = 'grid';
const VIEW_LIST = 'list';
function DocumentLibraryApp({
  restUrl
}) {
  const [items, setItems] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [folders, setFolders] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [search, setSearch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [activeFolder, setActiveFolder] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('all');
  const [view, setView] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(VIEW_GRID);
  const [perPage, setPerPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(10);
  const [page, setPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [total, setTotal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(0);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setLoading(true);
    setError(null);
    const params = new URLSearchParams({
      per_page: perPage,
      page,
      s: search
    });
    if (activeFolder !== 'all') {
      params.set('folder', activeFolder);
    }
    fetch(`${restUrl}?${params.toString()}`).then(res => res.json()).then(data => {
      // Flexible: accept either an array or a { items, folders, pagination } object.
      if (Array.isArray(data)) {
        setItems(data);
        setFolders([]);
        setTotal(data.length);
      } else {
        setItems(data.items || []);
        setFolders(data.folders || []);
        setTotal(data.pagination?.total || (data.items || []).length);
      }
    }).catch(err => {
      console.error('LDL fetch error', err);
      setError('Unable to load documents.');
    }).finally(() => setLoading(false));
  }, [restUrl, perPage, page, search, activeFolder]);
  const totalPages = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useMemo)(() => {
    return perPage ? Math.max(1, Math.ceil(total / perPage)) : 1;
  }, [total, perPage]);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `ldl-wrapper ldl-view-${view}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("header", {
    className: "ldl-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    className: "ldl-title"
  }, "Document Library Demo"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-search"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "search",
    placeholder: "Search",
    value: search,
    onChange: e => {
      setSearch(e.target.value);
      setPage(1);
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-search-icon"
  }, "\uD83D\uDD0D")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-toolbar-right"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-per-page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Show files"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: perPage,
    onChange: e => {
      setPerPage(parseInt(e.target.value, 10));
      setPage(1);
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: 10
  }, "10"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: 20
  }, "20"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: 40
  }, "40"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-view-toggle",
    "aria-label": "Toggle view"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: view === VIEW_GRID ? 'is-active' : '',
    onClick: () => setView(VIEW_GRID),
    title: "Grid view"
  }, "\u25A6"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: view === VIEW_LIST ? 'is-active' : '',
    onClick: () => setView(VIEW_LIST),
    title: "List view"
  }, "\u2263")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-folders"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: activeFolder === 'all' ? 'is-active' : '',
    onClick: () => {
      setActiveFolder('all');
      setPage(1);
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-folder-name"
  }, "All"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-folder-count"
  }, total)), folders.map(folder => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    key: folder.id,
    className: activeFolder === String(folder.id) ? 'is-active' : '',
    onClick: () => {
      setActiveFolder(String(folder.id));
      setPage(1);
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-folder-name"
  }, folder.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-folder-count"
  }, folder.count))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("main", null, loading && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-status"
  }, "Loading documents\u2026"), !loading && error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-status ldl-status--error"
  }, error), !loading && !error && items.length === 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-status"
  }, "No documents found."), !loading && !error && items.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `ldl-items ldl-items-${view}`
  }, items.map(item => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(DocumentCard, {
    key: item.id,
    item: item
  }))), !loading && totalPages > 1 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-pagination"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    onClick: () => setPage(p => Math.max(1, p - 1)),
    disabled: page === 1
  }, "Prev"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Page ", page, " of ", totalPages), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    onClick: () => setPage(p => Math.min(totalPages, p + 1)),
    disabled: page === totalPages
  }, "Next"))));
}
function DocumentCard({
  item
}) {
  // Expecting something like: pdf, doc, xls, video, audio, image…
  const typeClass = item.type ? `ldl-type-${item.type}` : 'ldl-type-generic';
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("article", {
    className: `ldl-item-card ${typeClass}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-item-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-item-icon-label"
  }, (item.type || 'file').toUpperCase())), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-item-body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
    className: "ldl-item-title"
  }, item.title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "ldl-item-meta"
  }, item.size && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-item-size"
  }, item.size), item.folder_name && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ldl-item-folder"
  }, item.folder_name))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ldl-item-footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: item.url,
    className: "ldl-download-btn",
    target: "_blank",
    rel: "noopener noreferrer"
  }, "Download")));
}

// Mount on all shortcode roots.
document.addEventListener('DOMContentLoaded', () => {
  const roots = document.querySelectorAll('[data-ldl-root]');
  if (!roots.length) {
    return;
  }
  const restUrl = window.ldlDocumentLibrary && window.ldlDocumentLibrary.restUrl || '';
  roots.forEach(el => {
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.render)((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(DocumentLibraryApp, {
      restUrl: restUrl
    }), el);
  });
});

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*********************!*\
  !*** ./src/view.js ***!
  \*********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _LibrariesReactViews__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./LibrariesReactViews */ "./src/LibrariesReactViews.js");
// src/view.js




function hydrateNode(node) {
  const props = JSON.parse(node.getAttribute('data-props') || '{}');
  const params = new URLSearchParams();
  if (props.exclude?.length) params.set('exclude', props.exclude.join(','));
  if (props.limit) params.set('limit', String(props.limit));
  if (props.libraries?.length) params.set('libraries', props.libraries.join(','));
  if (props.categories?.length) params.set('categories', props.categories.join(','));
  params.set('layout', props.layout || 'list');
  params.set('search', String(!!props.search));
  params.set('nested', String(!!props.nested));
  _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
    path: `/ldl/v1/libraries?${params.toString()}`
  }).then(data => {
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LibrariesReactViews__WEBPACK_IMPORTED_MODULE_3__["default"], {
      data,
      layout: props.layout || 'list',
      search: !!props.search
    }), node);
  }).catch(() => {
    node.innerHTML = `<p class="ldl-error">${(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Unable to load libraries.', 'learndash-document-library')}</p>`;
  });
}
function boot() {
  document.querySelectorAll('[data-ldl-root]').forEach(node => {
    const props = JSON.parse(node.getAttribute('data-props') || '{}');
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LibrariesReactViews__WEBPACK_IMPORTED_MODULE_3__["default"], {
      apiParams: props,
      allowViewSwitch: true
    }), node);
  });
}
if (document.readyState !== 'loading') boot();else document.addEventListener('DOMContentLoaded', boot);

// function init() {
//   document.querySelectorAll('[data-ldl-root]').forEach(hydrateNode);
// }
// if (document.readyState !== 'loading') init();
// else document.addEventListener('DOMContentLoaded', init);
})();

/******/ })()
;
//# sourceMappingURL=view.js.map