// src/components/Pagination.jsx
export function Pagination({ totalItems, perPage, currentPage, onPageChange }) {
    const totalPages = Math.ceil(totalItems / perPage);
    if (totalPages <= 1) return null;

    const pages = [];
    for (let i = 1; i <= totalPages; i++) {
        // Add condensing like: 1 … 4 5 6 … 10
        if (
            i === 1 ||
            i === totalPages ||
            (i >= currentPage - 1 && i <= currentPage + 1)
        ) {
            pages.push(i);
        } else if (
            i === 2 && currentPage > 3 ||
            i === totalPages - 1 && currentPage < totalPages - 2
        ) {
            pages.push("...");
        }
    }

    return (
        <div className="flex items-center justify-end gap-2 mt-6 flex-wrap">
            <button
                onClick={() => onPageChange(currentPage - 1)}
                disabled={currentPage === 1}
                className={`px-1.5 py-1.5 text-xs rounded-md border transition cursor-pointer ${currentPage === 1
                    ? "text-gray-400 border-gray-200 !cursor-not-allowed"
                    : "text-gray-700 border-gray-300 hover:bg-gray-100"
                    }`}
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M10.8284 12.0007L15.7782 16.9504L14.364 18.3646L8 12.0007L14.364 5.63672L15.7782 7.05093L10.8284 12.0007Z"></path></svg>
            </button>

            {pages.map((p, idx) =>
                p === "..." ? (
                    <span key={`dots-${idx}`} className="px-2 text-gray-400 select-none">
                        …
                    </span>
                ) : (
                    <button
                        key={p}
                        onClick={() => onPageChange(p)}
                        className={`px-3 py-1.5 text-xs rounded-md border transition cursor-pointer ${p === currentPage
                            ? "bg-blue-500 text-white border-blue-500"
                            : "text-gray-700 border-gray-300 hover:bg-gray-100"
                            }`}
                    >
                        {p}
                    </button>
                )
            )}

            <button
                onClick={() => onPageChange(currentPage + 1)}
                disabled={currentPage === totalPages}
                className={`px-1.5 py-1.5 text-xs rounded-md border transition cursor-pointer ${currentPage === totalPages
                    ? "text-gray-400 border-gray-200 !cursor-not-allowed"
                    : "text-gray-700 border-gray-300 hover:bg-gray-100"
                    }`}
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M13.1717 12.0007L8.22192 7.05093L9.63614 5.63672L16.0001 12.0007L9.63614 18.3646L8.22192 16.9504L13.1717 12.0007Z"></path></svg>
            </button>
        </div>
    );
}
