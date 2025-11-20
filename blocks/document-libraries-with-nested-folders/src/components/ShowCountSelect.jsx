// ShowCountSelect.jsx

export function ShowCountSelect({
  value = 10,
  onChange,
  label = "Show files",
}) {
  const handleChange = (e) => {
    const raw = e.target.value;
    const num = Number(raw);
    // prevent NaN and negative
    onChange?.(Number.isNaN(num) || num <= 0 ? 1 : num);
  };

  return (
    <div className="inline-flex items-center gap-2 text-sm text-gray-600">
      <span>{label}</span>

      <input
        type="number"
        min={1}
        step={1}
        value={value} // <- controlled value
        onChange={handleChange}
        className="w-20 rounded-md border border-[#ffffff] focus:border-gray-200 bg-white px-3 py-1 text-sm  focus:outline-none focus:ring-none"
      />
    </div>
  );
}
