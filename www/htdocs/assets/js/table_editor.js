/**
 * TableEditor.js
 * Uma classe genérica para gerenciar tabelas editáveis no ABCD.
 * Autor: Roger C. Guilherme (Refactor)
 */

class TableEditor {
    constructor(tableId, options = {}) {
        this.tableId = tableId;
        this.tbody = document.querySelector(`#${tableId} tbody`);
        this.templateId = options.templateId || 'rowTemplate';
        this.sortable = null;

        if (!this.tbody) {
            console.error(`TableEditor: Tbody não encontrado para a tabela #${tableId}`);
            return;
        }

        if (options.enableDrag && typeof Sortable !== 'undefined') {
            this.initSortable(options.handleClass || '.drag-handle');
        }
    }

    addRow() {
        const template = document.getElementById(this.templateId);
        if (!template) return;

        let newRow;
        if (template.tagName === 'TEMPLATE') {
            newRow = template.content.cloneNode(true).querySelector('tr');
        } else {
            newRow = template.cloneNode(true);
            newRow.removeAttribute('id');
            newRow.classList.remove('d-none');
        }

        // Limpa inputs
        newRow.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.type !== 'hidden' && input.type !== 'button') input.value = '';
        });

        this.tbody.appendChild(newRow);
    }

    deleteRow(btn) {
        const row = btn.closest("tr");
        if (row) row.remove();
    }

    /* --- NOVO: Função Duplicar --- */
    duplicateRow(btn) {
        const row = btn.closest("tr");
        if (!row) return;

        // Clona a linha atual
        const newRow = row.cloneNode(true);

        // Insere logo após a linha original
        if (row.nextSibling) {
            this.tbody.insertBefore(newRow, row.nextSibling);
        } else {
            this.tbody.appendChild(newRow);
        }
    }

    moveRow(btn, direction) {
        const row = btn.closest("tr");
        if (direction === -1 && row.previousElementSibling) {
            this.tbody.insertBefore(row, row.previousElementSibling);
        } else if (direction === 1 && row.nextElementSibling) {
            this.tbody.insertBefore(row.nextElementSibling, row);
        }
    }

    initSortable(handleClass) {
        this.sortable = Sortable.create(this.tbody, {
            animation: 150,
            handle: handleClass,
            ghostClass: 'bg-light'
        });
    }

    collectData(rowProcessor) {
        const rows = this.tbody.querySelectorAll("tr");
        const data = [];
        rows.forEach(row => {
            // Ignora se for o próprio template (caso não esteja usando tag <template>)
            if (row.id === this.templateId) return;

            const result = rowProcessor(row);
            if (result !== null && result !== "") {
                data.push(result);
            }
        });
        return data.join("\n");
    }
}