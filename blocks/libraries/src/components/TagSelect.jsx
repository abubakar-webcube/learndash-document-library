export function TagSelect({ tags = [], value = [], onChange, placeholder = "Filter by tags" }) {
  const selected = value[0] ?? "";
  return (
    <div className="flex flex-col gap-1 min-w-[200px]">
      <select
        value={selected}
        onChange={(e) => onChange(e.target.value ? [Number(e.target.value)] : [])}
        className="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 px-3 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
      >
        <option value="">{placeholder}</option>
        {tags.map((tag) => (
          <option key={tag.id} value={tag.id}>{tag.name}</option>
        ))}
      </select>
    </div>
  );
}
