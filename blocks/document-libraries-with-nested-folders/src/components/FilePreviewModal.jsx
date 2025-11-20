import React, { useEffect, useState } from "react";
import * as XLSX from "xlsx";

export default function FilePreviewModal({ file, onClose }) {
    if (!file) return null;

    const { url, type, title } = file;
    const [csvData, setCsvData] = useState([]);

    // Fetch and parse CSV if file type is CSV
    useEffect(() => {
        if (type?.toLowerCase() === "csv") {
            fetch(url)
                .then((res) => res.text())
                .then((text) => {
                    const workbook = XLSX.read(text, { type: "string" });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const data = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                    setCsvData(data);
                })
                .catch(() => setCsvData([["Cannot load CSV"]]));
        }
    }, [url, type]);

    const renderPreview = () => {
        const lowerType = type?.toLowerCase();

        if (lowerType === "pdf") {
            return <iframe src={url} width="100%" height="100%" />;
        }

        if (["docx", "xlsx", "pptx"].includes(lowerType)) {
            const officeUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(
                url
            )}`;
            return <iframe src={officeUrl} width="100%" height="100%" />;
        }

        if (["png", "jpg", "jpeg", "gif"].includes(lowerType)) {
            return (
                <img
                    src={url}
                    alt={title}
                    className="max-h-full max-w-full mx-auto w-full rounded-[10px]"
                />
            );
        }

        if (lowerType === "mp4") {
            return (
                <video
                    controls
                    className="max-h-full max-w-full w-full rounded-[20px] mx-auto"
                >
                    <source src={url} type="video/mp4" />
                </video>
            );
        }

        if (lowerType === "mp3") {
            return (
                <audio controls className="w-full">
                    <source src={url} type="audio/mpeg" />
                </audio>
            );
        }

        if (lowerType === "csv") {
            if (!csvData.length) return <p>Loading CSV...</p>;

            return (
                <div className="w-full h-full overflow-auto p-2 bg-[#f9f9f9] rounded-[10px]">
                    <table className="table-auto w-full border-collapse text-sm">
                        <tbody>
                            {csvData.map((row, i) => (
                                <tr key={i} className="border-b border-gray-300">
                                    {row.map((cell, j) => (
                                        <td key={j} className="border px-2 py-1">
                                            {cell}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            );
        }

        if (lowerType === "json") {
            return (
                <iframe
                    src={url}
                    width="100%"
                    height="100%"
                    className="rounded-[10px]"
                />
            );
        }

        return (
            <div className="text-center">
                <p>Cannot preview this file type.</p>
                <a
                    href={url}
                    download={title}
                    className="text-blue-600 underline mt-2 inline-block"
                >
                    Download {title}
                </a>
            </div>
        );
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-[#0000009e] backdrop-blur-[10px]">
            <button
                onClick={onClose}
                className="absolute top-2 right-2 h-[24px] w-[24px] bg-[#fff] rounded-[6px] hover:bg-[#e4e4e4] cursor-pointer flex justify-center items-center"
            >
                ✕
            </button>
            <div className="relative w-[80%] max-w-4xl h-[80%] bg-white rounded shadow-lg p-4 overflow-auto rounded-[10px]">
                <h3 className="text-lg font-semibold mb-2">{title}</h3>
                <div className="h-full w-full flex items-center justify-center the-viewer max-h-[calc(100%_-_36px)] overflow-hidden rounded-[10px]">
                    {renderPreview()}
                </div>
            </div>
        </div>
    );
}
