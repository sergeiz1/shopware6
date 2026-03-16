import template from './sw-product-list.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-product-list', {
    inject: ['repositoryFactory', 'filterFactory'],
    template,

    data() {
        return {
            productTypeExtensionRepository: null,
            wbmProductTypeOptions: [],
            wbmSelectedProductTypes: [],
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
                    options: this.wbmProductTypeOptions,
                    loading: this.wbmProductTypeLoading,
                }
            };
        },
    },

    created() {
         if (Array.isArray(this.defaultFilters) && !this.defaultFilters.includes('product-type-filter')) {
            this.defaultFilters.push('product-type-filter');
        }

        this.productTypeExtensionRepository = this.repositoryFactory.create('product_type_extension');
        this.wbmLoadProductTypes();
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
            criteria.addAssociation('wbmProductTypeExtension');

            return criteria;
        },

        async loadProductTypes() {
            try {
                const criteria = new Criteria(1, 500);
                criteria.addSorting(Criteria.sort('productType', 'ASC', false));
                criteria.addAggregation(Criteria.terms('types', 'productType'));

                const result = await this.productTypeExtensionRepository.search(criteria, Shopware.Context.api);
                const buckets = result.aggregations?.types?.buckets || [];

                const newOptions = buckets
                    .map(b => String(b.key || '').trim())
                    .filter(Boolean)
                    .sort()
                    .map(type => ({ id: type, name: type }));

                this.wbmProductTypeOptions.splice(0, this.wbmProductTypeOptions.length, ...newOptions);
            } catch (e) {
                console.error('WbmProductTypeFilter: loadProductTypes failed', e);
                this.wbmProductTypeOptions = [];
            }
        },
    },
});
