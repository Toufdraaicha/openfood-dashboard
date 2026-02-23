import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['body', 'config']
    static values = {
        id: String,
        type: String,
        dataUrl: String
    }

    connect() {
        console.log('Widget connected:', this.idValue, this.typeValue)
        this.loadData()
    }

    async loadData() {
        try {
            const response = await fetch(this.dataUrlValue)
            const json = await response.json()
            this.renderData(json.data)
        } catch (error) {
            console.error('Widget load error:', error)
            this.renderError()
        }
    }

    renderData(data) {
        switch(this.typeValue) {
            case 'products_search':
            case 'category_top':
                this.renderProductList(data)
                break
            case 'nutri_score_stats':
                this.renderNutriScore(data)
                break
            case 'product_detail':
                this.renderProductDetail(data)
                break
        }
    }

    renderProductList(products) {
        if (!products || products.length === 0) {
            this.bodyTarget.innerHTML = '<div class="text-center py-8 text-gray-600 text-sm">Aucun produit</div>'
            return
        }

        const nutriColors = {
            'A': 'bg-green-500', 'B': 'bg-lime-500', 'C': 'bg-yellow-500',
            'D': 'bg-orange-500', 'E': 'bg-red-500', '?': 'bg-gray-500'
        }

        const html = `
            <ul class="space-y-2">
                ${products.slice(0, 5).map(p => `
                    <li class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-800 transition">
                        ${p.image
            ? `<img src="${p.image}" class="w-10 h-10 object-contain rounded bg-white" onerror="this.style.display='none'">`
            : '<div class="w-10 h-10 bg-gray-800 rounded flex items-center justify-center text-gray-600 text-xs">?</div>'
        }
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white truncate">${p.name || 'Inconnu'}</p>
                            <p class="text-xs text-gray-500 truncate">${p.brand || ''}</p>
                        </div>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded text-white ${nutriColors[p.nutriscore] || 'bg-gray-500'}">
                            ${p.nutriscore || '?'}
                        </span>
                    </li>
                `).join('')}
            </ul>
        `
        this.bodyTarget.innerHTML = html
    }

    renderNutriScore(stats) {
        if (!stats) {
            this.bodyTarget.innerHTML = '<div class="text-center py-8 text-gray-600 text-sm">Pas de données</div>'
            return
        }

        const colors = {
            'A': 'bg-green-500', 'B': 'bg-lime-500', 'C': 'bg-yellow-500',
            'D': 'bg-orange-500', 'E': 'bg-red-500', '?': 'bg-gray-700'
        }
        const total = Object.values(stats).reduce((a, b) => a + b, 0) || 1

        const html = `
            <div class="space-y-2">
                ${Object.entries(stats).filter(([k]) => k !== '?').map(([grade, count]) => `
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold w-5 text-center text-white px-1 py-0.5 rounded ${colors[grade]}">
                            ${grade}
                        </span>
                        <div class="flex-1 bg-gray-800 rounded-full h-2">
                            <div class="h-2 rounded-full ${colors[grade]}" style="width: ${Math.round(count/total*100)}%"></div>
                        </div>
                        <span class="text-xs text-gray-400 w-6 text-right">${count}</span>
                    </div>
                `).join('')}
            </div>
        `
        this.bodyTarget.innerHTML = html
    }

    renderProductDetail(product) {
        if (!product || !product.name) {
            this.bodyTarget.innerHTML = '<div class="text-center py-8 text-gray-600 text-sm">Configurez le code-barres</div>'
            return
        }

        const nutriColors = {
            'A': 'bg-green-500', 'B': 'bg-lime-500', 'C': 'bg-yellow-500',
            'D': 'bg-orange-500', 'E': 'bg-red-500', '?': 'bg-gray-500'
        }

        const html = `
            <div class="flex gap-4 items-start">
                ${product.image
            ? `<img src="${product.image}" class="w-16 h-16 object-contain rounded bg-white shrink-0">`
            : ''
        }
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-white truncate">${product.name}</p>
                    <p class="text-xs text-gray-500 mt-0.5">${product.brand || ''}</p>
                    <span class="inline-block mt-2 text-sm font-bold px-2 py-0.5 rounded text-white ${nutriColors[product.nutriscore] || 'bg-gray-500'}">
                        Nutri-Score ${product.nutriscore || '?'}
                    </span>
                </div>
            </div>
        `
        this.bodyTarget.innerHTML = html
    }

    renderError() {
        this.bodyTarget.innerHTML = '<div class="text-center py-8 text-red-400 text-sm">⚠️ Erreur de chargement</div>'
    }

    openConfig(event) {
        event.preventDefault()
        window.location.href = `/dashboard/widget/${this.idValue}/edit`
    }

    async delete(event) {
        event.preventDefault()
        if (!confirm('Supprimer ce widget ?')) return

        try {
            const response = await fetch(`/dashboard/widget/${this.idValue}/delete`, {
                method: 'DELETE'
            })
            if (response.ok) {
                this.element.remove()
            }
        } catch (error) {
            console.error('Delete error:', error)
        }
    }

    async refresh() {
        this.bodyTarget.innerHTML = '<div class="flex items-center justify-center h-32"><span class="text-gray-600 text-sm animate-pulse">Actualisation...</span></div>'
        await this.loadData()
    }
}
