Module.preInit = function() {
    FS.mkdir('/preload');
    FS.mkdir('/preload/wordpress');
    FS.mount(NODEFS, { root: './wordpress' }, '/preload/wordpress');
    