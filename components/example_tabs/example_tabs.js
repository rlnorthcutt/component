const pre = document.querySelector('pre');
document.querySelector('.run').addEventListener('click', () => {
  eval(`(function(){ ${pre.innerText} })();`);
});

/* TAB CODE */
class TabContainer extends HTMLElement {
    constructor() {
        super();

        this._headerItems = this.querySelectorAll('tab-header-item');
        this._bodyItems = this.querySelectorAll('tab-body-item');
        this._index = 0;

    for(let i = 0; i < this._headerItems.length; i++)
      this._headerItems[i].addEventListener('click', this.select.bind(this, i));
    }
    select(index) {
        if (this._index === index || index < 0 || index > this._headerItems.length - 1)
            return;

        this._headerItems[this._index].classList.remove('tab-active');
        this._headerItems[index].classList.add('tab-active');
        this._index = index;

    for(let item of this._bodyItems)
          item.style.transform = `translateX(${(-this._index) * 100}%)`;
    }
}
customElements.define('tab-container', TabContainer);
