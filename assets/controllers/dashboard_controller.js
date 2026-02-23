import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static targets = ['grid']
    static values = { reorderUrl: String }

    connect() {
        this.initSortable()
    }

    initSortable() {
        if (!this.hasGridTarget) return

        this.sortable = new Sortable(this.gridTarget, {
            animation: 200,
            handle: '.widget-handle',
            ghostClass: 'opacity-50',
            onEnd: this.handleReorder.bind(this)
        })
    }

    async handleReorder(event) {
        const order = Array.from(this.gridTarget.children).map(
            el => el.dataset.widgetId
        )

        try {
            await fetch(this.reorderUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order })
            })
        } catch (error) {
            console.error('Reorder error:', error)
        }
    }

    async addWidget(event) {
        const type = event.params.type

        try {
            const response = await fetch('/dashboard/widget/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type })
            })

            if (response.ok) {
                window.location.reload()
            }
        } catch (error) {
            console.error('Add widget error:', error)
        }
    }

    closeModal() {
        document.getElementById('modal-add-widget').classList.add('hidden')
    }

    openModal() {
        document.getElementById('modal-add-widget').classList.remove('hidden')
    }
}
