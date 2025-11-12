// src/components/Breadcrumbs.jsx

export function Breadcrumbs({
  rootLabel = "Document Library Demo",
  currentLabel = "All documents",
}) {
  return (
    <nav
      className="flex items-center gap-2 text-sm text-gray-600"
      aria-label="Breadcrumb"
    >
      {/* Small blue folder/document icon */}
      <span className="inline-flex h-5 w-5 items-center justify-center rounded bg-blue-50 text-blue-600">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="h-3.5 w-3.5"
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
      </span>

      <ol className="flex items-center gap-1">
        <li className="font-medium text-gray-800">{rootLabel}</li>
        <li className="text-gray-400">/</li>
        <li className="text-gray-500">{currentLabel}</li>
      </ol>
    </nav>
  );
}
