/**
 * Example config custom element. This is an example of how to 
 *   pass component config data from the CMS to the component in JS.
 **/

class ConfigContainer extends HTMLElement {

  /**
  * Custom element constructor.
  */
  constructor() {
    super();
    // Get the config items from Drupal that are passed as data attributes.
    this.config = Object.assign({}, this.parentNode.dataset);
    // Convert array
    this.config.interests = JSON.parse(this.config.interests);
    // Build a table and insert it with the values
    let tableData = Object.entries(this.config);
    this.appendChild(this.buildTable(tableData));
  }

  /**
   * Function to build a table and output the results of an array
   */
  buildTable(tableData) {
    var table = document.createElement('table');
    var tableBody = document.createElement('tbody');
    // Build a row from each array item
    tableData.forEach(function (rowData) {
      var row = document.createElement('tr');
      // Build cells for each row
      rowData.forEach(function (cellData) {
        var cell = document.createElement('td');
        cell.appendChild(document.createTextNode(cellData));
        row.appendChild(cell);
      });
      tableBody.appendChild(row);
    });
    table.appendChild(tableBody);
    return table;
  }
}

customElements.define('config-container', ConfigContainer);
