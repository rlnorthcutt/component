/* This needs to possibly put into a drupal behavior */

/* TAB CODE */
class TabContainer extends HTMLElement {

  /**
  * Custom element constructor.
  * @todo rewrite this to use more jquery and be simpler
  */
  constructor() {
    super();

    this._config = jQuery(this.parentNode).data();
    this._headerItems = this.querySelectorAll('tab-header-item');
    this._bodyItems = this.querySelectorAll('tab-body-item');
    this._index = 0;

    // Loop through the tabs
    for (let i = 0; i < this._headerItems.length; i++) {
      let id = this._headerItems[i].getAttribute('id');
      this._headerItems[i].addEventListener('click', this.select.bind(this, i));
      this._headerItems[i].innerHTML = this._config[id];
    }
  }

  /**
  * Tab select method
  */  
  select(index) {
    if (this._index === index || index < 0 || index > this._headerItems.length - 1)
        return;

    this._headerItems[this._index].classList.remove('tab-active');
    this._headerItems[index].classList.add('tab-active');
    this._index = index;

    for(let item of this._bodyItems) {
      item.style.transform = `translateX(${(-this._index) * 100}%)`;
    }
  }

}

customElements.define('tab-container', TabContainer);


/* Wires up the example interactions in the 2nd tab */
const pre = document.querySelector('pre');
document.querySelector('.run').addEventListener('click', () => {
  eval(`(function(){ jQuery{pre.innerText} })();`);
});
