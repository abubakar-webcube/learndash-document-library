jQuery(document).ready(function ($) {
    function initializeDocPreview() {
        const modal = $('#ldlDocPreviewModal');
        const viewer = $('#ldlDocViewer');
        const titleEl = $('#ldlDocTitle');
        // Close modal
        $('.ldl-modal-close').on('click', function () {
            modal.hide();
            viewer.empty();
        });
        // Click event on title
        $('.ldl_doc_view').on('click', function () {
            const url = $(this).data('doc-url');
            const title = $(this).data('doc-title');

            titleEl.text(title);
            viewer.html('<p>Loading preview...</p>');
            modal.show();

            const ext = url.split('.').pop().toLowerCase();

            // Handle different file types
            if (ext === 'pdf') {
                // PDF.js viewer loading with blob workaround
                (async () => {
                    try {
                        // Fetch the PDF via WordPress (bypasses CORS and auth)
                        const response = await fetch(ldlDocPreview.ajaxurl, {
                            method: 'POST',
                            body: new URLSearchParams({
                                action: 'ldl_get_pdf_file',
                                file_url: url
                            })
                        });

                        if (!response.ok) throw new Error(`HTTP ${response.status} - ${response.statusText}`);

                        const blob = await response.blob();
                        if (blob.size === 0) throw new Error('File is empty or inaccessible.');

                        const blobUrl = URL.createObjectURL(blob);

                        const viewerPath = `${window.location.origin}/wp-content/plugins/learndash-document-library/public/pdfjs/web/viewer.html?file=${encodeURIComponent(blobUrl)}`;
                        const iframe = $('<iframe>')
                            .attr('src', viewerPath)
                            .css({
                                width: '100%',
                                height: '100%',
                                border: 'none'
                            });

                        viewer.html(iframe);
                    } catch (error) {
                        console.error('Failed to load PDF into viewer:', error);
                        viewer.html(`
                            <div style="color:red;padding:20px;text-align:center;">
                                <strong>Error:</strong> Unable to load PDF.<br>${error.message}
                            </div>
                        `);
                    }
                })();
            } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) {
                const officeUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(url);

                // Try Microsoft Viewer first (it's more visually accurate)
                const iframe = $('<iframe>')
                    .attr('src', officeUrl)
                    .attr('width', '100%')
                    .attr('height', '100%')
                    .on('error', () => {
                    // Fallback to Mammoth/SheetJS if Microsoft viewer blocked
                    /*if (['doc', 'docx'].includes(ext)) {
                        fetch(url)
                        .then(res => res.arrayBuffer())
                        .then(ab => window.mammoth.convertToHtml({ arrayBuffer: ab }))
                        .then(result => viewer.html(result.value))
                        .catch(() => showError('Unable to preview this Word document.'));
                    } else if (['xls', 'xlsx', 'csv'].includes(ext)) {
                        fetch(url)
                        .then(res => res.arrayBuffer())
                        .then(ab => {
                            const wb = XLSX.read(ab, { type: 'array' });
                            const ws = wb.Sheets[wb.SheetNames[0]];
                            const html = XLSX.utils.sheet_to_html(ws);
                            viewer.html(html);
                        })
                        .catch(() => showError('Unable to preview this spreadsheet.'));
                    } else {*/
                        showError('Unable to load Office preview.');
                    //}
                    });

                viewer.html(iframe);
            } else if (['mp4','webm','ogg'].includes(ext)) {
                // Video
                viewer.html(`<video controls src="${url}"></video>`);
            } else if (['mp3','wav','ogg'].includes(ext)) {
                // Audio
                viewer.html(`<audio controls src="${url}"></audio>`);
            } else if (['docx','doc'].includes(ext)) {
                // Mammoth.js
                fetch(url)
                    .then(res => res.arrayBuffer())
                    .then(arrayBuffer => window.mammoth.convertToHtml({ arrayBuffer }))
                    .then(result => viewer.html(result.value))
                    .catch(() => viewer.html('<p>Unable to preview this document.</p>'));
            } else if (['xlsx','xls','csv'].includes(ext)) {
                // SheetJS
                fetch(url)
                    .then(res => res.arrayBuffer())
                    .then(ab => {
                    const wb = XLSX.read(ab, { type: 'array' });
                    const ws = wb.Sheets[wb.SheetNames[0]];
                    const html = XLSX.utils.sheet_to_html(ws);
                    viewer.html(html);
                    })
                    .catch(() => viewer.html('<p>Unable to preview this spreadsheet.</p>'));
            } else {
                viewer.html(`<iframe src="${url}" style="width:100%;height:80vh;"></iframe>`);
            }
        });

        $('#yourModalCloseButton').on('click', function () {
            const iframe = viewer.find('iframe')[0];
            if (iframe && iframe.src.startsWith('blob:')) {
                URL.revokeObjectURL(iframe.src);
            }
            viewer.empty();
        });
    }
    // make it accessible globally
    window.initializeDocPreview = initializeDocPreview;
    // Run once on initial page load
    initializeDocPreview();
    // Observe .ldl-view-documents for changes (AJAX updates)
    const observerTarget = document.querySelector('.ldl-view-documents');
    if (observerTarget) {
        const observer = new MutationObserver(function (mutationsList) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
            initializeDocPreview();
            }
        }
        });
        let rebindTimeout;
        observer.observe(observerTarget, { childList: true, subtree: true });
        observerTarget.addEventListener('DOMSubtreeModified', () => {
            clearTimeout(rebindTimeout);
            rebindTimeout = setTimeout(initializeDocPreview, 300);
        });
        // observer.observe(observerTarget, { childList: true, subtree: true });
    }
});
