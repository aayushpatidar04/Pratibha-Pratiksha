import * as XLSX from 'xlsx';

/**
 * Export an array of plain objects to a downloadable .xlsx file.
 * Usage: exportToExcel('Hostel Summary', [{ Building: 'Gyan Bhawan', Capacity: 54 }, ...])
 */
export function exportToExcel(filename, rows, sheetName = 'Sheet1') {
    if (!rows || !rows.length) return;

    const worksheet = XLSX.utils.json_to_sheet(rows);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
    XLSX.writeFile(workbook, `${filename}.xlsx`);
}