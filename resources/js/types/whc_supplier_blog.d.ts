export interface WhcSupplierBlog {
    id: number;
    offer_no: number;
    title: string;
    status: number;
    description: string;
    has_file: boolean;
    file_name: string;
    file_path: string;
    created_at_whc: string;
    updated_at_whc: string;
    created_at: string;
    updated_at: string;

    whc_supplier_offer_blog_magento: WhcSupplierOfferBlogMagento;
}

export interface WhcSupplierOfferBlogMagento {
    id: number;
    whc_supplier_offer_blog_id: number;
    magento_blog_id: number;
    status: number;
    url_key: string;
    created_magento_at: string;
}
