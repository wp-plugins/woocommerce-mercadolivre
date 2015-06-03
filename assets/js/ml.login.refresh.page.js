var old_tb_remove = window.tb_remove;

var tb_remove = function() {
    old_tb_remove(); // calls the tb_remove() of the Thickbox plugin
    window.location.assign( obj.url );
};