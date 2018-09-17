<script type="text/ecmascript-6">
    import $ from 'jquery';

    export default {
        props: ['type', 'message', 'autoClose', 'confirmationProceed', 'confirmationCancel'],

        data(){
            return {
                timeout: null,
                anotherModalOpened: $('body').hasClass('modal-open')
            }
        },


        mounted() {
            $('#alertModal').modal({
                backdrop: 'static',
            });

            $('#alertModal').on('hidden.bs.modal', e => {
                this.$root.alert.type = null;
                this.$root.alert.autoClose = false;
                this.$root.alert.message = '';
                this.$root.alert.confirmationProceed = null;
                this.$root.alert.confirmationCancel = null;

                if (this.anotherModalOpened) {
                    $('body').addClass('modal-open');
                }
            });

            if (this.autoClose) {
                this.timeout = setTimeout(() => {
                    this.close();
                }, this.autoClose);
            }
        },


        methods: {
            /**
             * Close the modal.
             */
            close(){
                clearTimeout(this.timeout);

                $('#alertModal').modal('hide');
            },


            /**
             * Confirm and close the modal.
             */
            confirm(){
                this.confirmationProceed();

                this.close();
            },


            /**
             * Cancel and close the modal.
             */
            cancel(){
                if (this.confirmationCancel) {
                    this.confirmationCancel();
                }

                this.close();
            }
        }
    }
</script>

<template>
    <div class="modal" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" v-if="type == 'confirmation'" class="fill-warning">
                        <path d="M10 20a10 10 0 1 1 0-20 10 10 0 0 1 0 20zm2-13c0 .28-.21.8-.42 1L10 9.58c-.57.58-1 1.6-1 2.42v1h2v-1c0-.29.21-.8.42-1L13 9.42c.57-.58 1-1.6 1-2.42a4 4 0 1 0-8 0h2a2 2 0 1 1 4 0zm-3 8v2h2v-2H9z"/>
                    </svg>

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" v-if="type == 'success'" class="fill-success">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"/>
                    </svg>

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" v-if="type == 'error'" class="fill-danger">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/>
                    </svg>

                    <p class="mt-3 mb-0">{{message}}</p>
                </div>


                <div class="modal-footer justify-content-center">

                    <button v-if="type == 'error'" class="btn btn-outline-danger btn-sm" @click="close">
                        CLOSE
                    </button>

                    <button v-if="type == 'success'" class="btn btn-outline-success btn-sm" @click="close">
                        OK
                    </button>


                    <button v-if="type == 'confirmation'" class="btn btn-outline-danger btn-sm" @click="confirm">
                        YES
                    </button>
                    <button v-if="type == 'confirmation'" class="btn btn-outline-success btn-sm" @click="cancel">
                        NO, CANCEL
                    </button>

                </div>
            </div>
        </div>
    </div>
</template>

<style>
    #alertModal {
        z-index: 99999;
        background: rgba(0, 0, 0, 0.5);
    }

    #alertModal svg {
        display: block;
        margin: 0 auto;
        width: 4rem;
        height: 4rem;
    }
</style>
