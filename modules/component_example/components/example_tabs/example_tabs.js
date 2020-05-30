/**
 * Example tab custom element. This uses vanilla JS to create a tabbed
 *   container. Note that this is not wrapped in a behavior because it
 *   is a self contained custom element that is only rendered when needed.
 **/

class TabContainer extends HTMLElement {

  /**
  * Custom element constructor.
  */
  constructor() {
    super();
    // Get the config items from Drupal that are passed as data attributes.
    this.config = Object.assign({}, this.parentNode.dataset) ;
    this.tabs = this.querySelectorAll('tab-header-item');
    this.index = 0;

    // Add the select to each tab and set the tab text from config.
    this.tabs.forEach(function (tab, i) {
      tab.innerHTML = this.config[tab.id];
      tab.addEventListener('click', this.select.bind(this, i));
    }, this);
  }

  /**
  * Tab select method
  */  
  select(index) {
    // Escape the function if needed
    if (this.index === index || index < 0 || index > this.tabs.length - 1) {
        return;
    }

    // Change the active class and the current index
    this.tabs[this.index].classList.remove('tab-active');
    this.tabs[index].classList.add('tab-active');
    this.index = index;

    // Move the content
    for (let item of this.querySelectorAll('tab-body-item')) {
      item.style.transform = `translateX(${(-this.index) * 100}%)`;
    }
  }

}

customElements.define('tab-container', TabContainer);
