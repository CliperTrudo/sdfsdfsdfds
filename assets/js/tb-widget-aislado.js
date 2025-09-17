class TBWidgetAislado extends HTMLElement {
  constructor() {
    super();
    const root = this.attachShadow({ mode: 'open' });
    root.innerHTML = `
      <style>
        :host { display:block; font:14px/1.4 system-ui, sans-serif; }
        .card { padding:16px; border:1px solid #ddd; border-radius:8px; }
        .title { margin:0 0 8px; font-weight:600; }
      </style>
      <div class="card">
        <h3 class="title">Contenido aislado</h3>
        <div class="content"><slot></slot></div>
      </div>
    `;
  }
}
customElements.define('tb-widget-aislado', TBWidgetAislado);
