(() => {
    const printReceipt = () => window.print();
    document.getElementById('print-receipt').addEventListener('click', printReceipt);
    // Print in this tab after fonts load; no popup permissions are needed.
    const ready = document.fonts ? document.fonts.ready : Promise.resolve();
    ready.then(() => requestAnimationFrame(() => requestAnimationFrame(printReceipt)));
})();
