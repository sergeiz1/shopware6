import template from './sw-product-detail-specifications.html.twig';
import './sw-product-detail.specifications.scss';

Shopware.Component.override('sw-product-detail-specifications', {
    template,

    computed: {
        extension() {
            this.ensureExtension();
            return this.product.extensions.productTypeExtension;
        }
    },

    created() {
        this.ensureExtension();
    },

    watch: {
        product: {
            handler() {
                this.ensureExtension();
            },
            immediate: true
        }
    },

    methods: {
        ensureExtension() {
            if (!this.product) {
                return;
            }

            if (!this.product.extensions) {
                this.product.extensions = {};
            }

            if (!this.product.extensions.productTypeExtension) {
                this.product.extensions.productTypeExtension = {
                    productType: '',
                    productIdFromApi: null,
                };
            }
        }
    }
});
