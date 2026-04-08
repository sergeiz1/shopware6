import template from './sw-product-list.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-product-list', {
    inject: ['repositoryFactory', 'filterFactory'],
    template,

    data() {
        return {
            productTypeExtensionRepository: null,
            productTypeOptions: [],
            productTypeLoading: false,
        };
    },

    computed: {
        listFilters() {
            const filters = this.$super('listFilters');

            return [
                ...filters,
                'product-type-filter',
            ];
        },

        listFilterOptions() {
            const options = this.$super('listFilterOptions');

            return {
                ...options,
                'product-type-filter': {
                    property: 'productTypeExtension.productType',
                    type: 'multi-select-filter',
                    label: this.$tc('sw-product.filter.productType'),
                    placeholder: this.$tc('sw-product.filter.placeholder'),
                    valueProperty: 'id',
                    labelProperty: 'name',
                    options: this.productTypeOptions,
                    loading: this.productTypeLoading,
                }
            };
        },
    },

    created() {
        if (Array.isArray(this.defaultFilters) && !this.defaultFilters.includes('product-type-filter')) {
            this.defaultFilters.push('product-type-filter');
        }

        this.productTypeExtensionRepository = this.repositoryFactory.create('sz_product_type_extension');
        this.loadProductTypes();
    },

    methods: {
        getProductColumns() {
            const columns = this.$super('getProductColumns');
            const exists = columns.some(col => col.property === 'productType');

            if (!exists) {
                columns.push({
                    property: 'productType',
                    dataIndex: 'productTypeExtension.productType',
                    label: this.$tc('sw-product.list.columnProductType'),
                    inlineEdit: 'string',
                    allowResize: true,
                    sortable: false,
                });
            }
            return columns;
        },

        getListCriteria() {
            const criteria = this.$super('getListCriteria');
            criteria.addAssociation('productTypeExtension');

            return criteria;
        },

        async loadProductTypes() {
            this.productTypeLoading = true;

            try {
                const criteria = new Criteria(1, 500);
                criteria.addAggregation(Criteria.terms('types', 'productType'));

                const result = await this.productTypeExtensionRepository.search(criteria, Shopware.Context.api);
                const buckets = result.aggregations?.types?.buckets || [];

                const newOptions = buckets
                    .map(b => String(b.key || '').trim())
                    .filter(Boolean)
                    .sort()
                    .map(type => ({ id: type, name: type }));

                this.productTypeOptions.splice(0, this.productTypeOptions.length, ...newOptions);
            } catch (e) {
                console.error('ProductTypeFilter: loadProductTypes failed', e);
                this.productTypeOptions = [];
            } finally {
                this.productTypeLoading = false;
            }
        },
    },
});
