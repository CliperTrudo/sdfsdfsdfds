(() => {
  if (customElements.get('tb-widget-aislado')) return;

  class TBWidgetAislado extends HTMLElement {
    constructor() {
      super();
      const root = this.attachShadow({ mode: 'open' });
      root.innerHTML = `
        <style>
          :host { display:block; font:14px/1.4 system-ui, sans-serif; }
          .card { padding:16px; border:1px solid #ddd; border-radius:8px; background: var(--tb-card-bg, #fff); color: var(--tb-card-fg, #222); }
          .title { margin:0 0 8px; font-weight:600; }
        </style>
        <div class="card" part="card">
          <h3 class="title" part="title">Contenido aislado</h3>
          <div class="content" part="content"></div>
        </div>
      `;
      const content = root.querySelector('.content');
      while (this.firstChild) content.appendChild(this.firstChild);
    }
  }
  customElements.define('tb-widget-aislado', TBWidgetAislado);
})();
