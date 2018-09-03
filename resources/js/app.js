import Vue from 'vue'
import Base from './base'
import Routes from './routes'
import VueRouter from 'vue-router'

require('bootstrap');

Vue.use(VueRouter);

window.Popper = require('popper.js').default;

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: '/telescope/',
});

Vue.component('loader', require('./components/loader.vue'));
Vue.component('index-screen', require('./components/IndexScreen.vue'));
Vue.component('preview-screen', require('./components/PreviewScreen.vue'));

Vue.mixin(Base);

new Vue({
    el: '#telescope',

    router,
});
