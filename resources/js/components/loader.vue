<script type="text/ecmascript-6">
    export default {
        props: ['loading'],

        components: {},

        /**
         * The component's data.
         */
        data() {
            return {};
        },


        /**
         * Prepare the component.
         */
        mounted() {
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed(){
        },


        methods: {}
    }
</script>

<template>
    <div v-if="!loading">
        Loading...
    </div>
</template>


<template>
    <div>
        <div v-if="loading">
            Loading...
        </div>

        <slot v-else />
    </div>
</template>