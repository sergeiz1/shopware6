import template from './sw-product-detail-specifications.html.twig';
import './sw-product-detail.specifications.scss';

Shopware.Component.override('sw-product-detail-specifications', {
    template,

    computed: {
        wbmExtension() {
            this.ensureWbmExtension();
            return this.product.extensions.wbmProductTypeExtension;
        }
    },

    created() {
        this.ensureWbmExtension();
    },

    watch: {
        product: {
            handler() {
                this.ensureWbmExtension();
            },
            immediate: true
        }
    },

    methods: {
        ensureWbmExtension() {
            if (!this.product) {
                return;
            }

            if (!this.product.extensions) {
                this.product.extensions = {};
            }

            if (!this.product.extensions.wbmProductTypeExtension) {
                this.product.extensions.wbmProductTypeExtension = {
                    productType: '',
                    productIdFromApi: null,
                };
            }
        }
    }
});
