import template from './sw-product-list.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-product-list', {
    inject: ['repositoryFactory', 'filterFactory'],
    template,

    data: function () {
        return {
            productTypeExtensionRepository: null,
            wbmProductTypeOptions: [],
            wbmSelectedProductTypes: [],
        };
    },

    computed: {
        defaultFilters() {
            const defaults = this.$super('defaultFilters');

            return defaults.includes('wbm-product-type-filter')
                ? defaults
                : [...defaults, 'wbm-product-type-filter'];
        },

        listFilterOptions() {
            const options = this.$super('listFilterOptions');

            return {
                ...options,
                'wbm-product-type-filter': {
                    property: 'wbmProductTypeExtension.productType',
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
        this.productTypeExtensionRepository = this.repositoryFactory.create('wbm_product_type_extension');
        this.wbmLoadProductTypes();
    },

    methods: {
        getProductColumns() {
            const columns = this.$super('getProductColumns');
            const exists = columns.some(col => col.property === 'wbmProductType');

            if (!exists) {
                columns.push({
                    property: 'wbmProductType',
                    dataIndex: 'wbmProductTypeExtension.productType',
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

            if (this.wbmSelectedProductTypes.length > 0) {
                criteria.addFilter(
                    Criteria.equalsAny(
                        'wbmProductTypeExtension.productType',
                        this.wbmSelectedProductTypes
                    )
                );
            }

            return criteria;
        },

        async wbmLoadProductTypes() {
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

        wbmOnProductTypeFilterChange(values) {
            this.wbmSelectedProductTypes = Array.isArray(values) ? values : [];

            if (typeof this.getList === 'function') {
                this.getList();
            } else if (typeof this.refreshList === 'function') {
                this.refreshList();
            }
        },
    },
});
