import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

/**
 * Renders a PDF (poin 17) inside `containerId`, one <canvas> per page, with
 * simple page navigation. `pdfUrl` must point at DocumentPreviewController —
 * it is fetched with credentials so the session-based authorization applies.
 */
export async function initPdfViewer(containerId, pdfUrl) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const statusEl = container.querySelector('[data-pdf-status]');
    const canvas = container.querySelector('[data-pdf-canvas]');
    const pageLabel = container.querySelector('[data-pdf-page-label]');
    const prevBtn = container.querySelector('[data-pdf-prev]');
    const nextBtn = container.querySelector('[data-pdf-next]');
    const context = canvas.getContext('2d');

    let pdfDocument = null;
    let currentPage = 1;

    const setStatus = (text) => {
        if (statusEl) statusEl.textContent = text;
    };

    const renderPage = async (pageNumber) => {
        const page = await pdfDocument.getPage(pageNumber);
        const containerWidth = container.clientWidth || 800;
        const unscaledViewport = page.getViewport({ scale: 1 });
        const scale = Math.min(containerWidth / unscaledViewport.width, 1.5);
        const viewport = page.getViewport({ scale });

        canvas.width = viewport.width;
        canvas.height = viewport.height;

        await page.render({ canvasContext: context, viewport }).promise;

        if (pageLabel) {
            pageLabel.textContent = `Halaman ${pageNumber} / ${pdfDocument.numPages}`;
        }
    };

    try {
        setStatus('Memuat dokumen…');
        pdfDocument = await pdfjsLib.getDocument({ url: pdfUrl, withCredentials: true }).promise;
        setStatus('');
        canvas.classList.remove('hidden');
        await renderPage(currentPage);
    } catch (error) {
        setStatus('Gagal memuat preview PDF. Silakan unduh dokumen untuk melihatnya.');
        return;
    }

    prevBtn?.addEventListener('click', async () => {
        if (currentPage <= 1) return;
        currentPage -= 1;
        await renderPage(currentPage);
    });

    nextBtn?.addEventListener('click', async () => {
        if (currentPage >= pdfDocument.numPages) return;
        currentPage += 1;
        await renderPage(currentPage);
    });
}

window.initPdfViewer = initPdfViewer;
