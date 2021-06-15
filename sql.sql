CREATE TABLE users (
user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
first_name VARCHAR(20) NOT NULL,
last_name VARCHAR(40) NOT NULL,
email VARCHAR(80) NOT NULL,
pass VARCHAR(255) NOT NULL,
user_level TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
active CHAR(32),
registration_date DATETIME NOT NULL,
PRIMARY KEY (user_id),
UNIQUE KEY (email),
INDEX login (email, pass)
);


CREATE TABLE products (
        product_id          INT UNSIGNED        NOT NULL AUTO_INCREMENT
    ,   product_title       varchar(20)         NOT NULL
    ,   product_description varchar(1000)       NULL
    ,   price               decimal(19,4)       NOT NULL DEFAULT 0 
    ,   stock_quantity      BIGINT(20)          NOT NULL DEFAULT 0
    ,   total_sales         BIGINT(20)          NOT NULL DEFAULT 0
    ,   stock_status        varchar(100)        NULL
    ,   average_rating      decimal(3,2)        NOT NULL DEFAULT 0.00
    ,   sku                 varchar(100)        NULL
    ,   rating_count        BIGINT(20)          NOT NULL DEFAULT 0
    ,   created_date        DATETIME            NOT NULL DEFAULT NOW()
    ,   created_by_user_id  INT                 NOT NULL  
    ,   last_modified       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ,   PRIMARY KEY(product_id)
    ,   UNIQUE KEY(sku) 
    ,   INDEX xi_product_title(product_title)
    ,   INDEX xi_product_price(price)   
);

CREATE TABLE images (
        image_id            INT UNSIGNED        NOT NULL AUTO_INCREMENT
    ,   image_title         varchar(20)         NULL
    ,   image_size          decimal(20,4)       NOT NULL
    ,   image_type          varchar(20)         NOT NULL
    ,   created_by_user_id  INT                 NOT NULL
    ,   created_date        DATETIME            NOT NULL DEFAULT NOW()
    ,   last_modified       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ,   image_name          varchar(255)        NOT NULL
    ,   PRIMARY KEY(image_id)
    ,   INDEX xi_image_name(image_name)
);

CREATE TABLE product_images (
        product_image_id    INT UNSIGNED            NOT NULL AUTO_INCREMENT
    ,   product_id          INT UNSIGNED            NOT NULL
    ,   image_id            INT UNSIGNED             NOT NULL
    ,   cover_image         BIT                     NOT NULL DEFAULT 0
    ,   created_by_user_id  INT                     NOT NULL      
    ,   created_date        DATETIME                NOT NULL DEFAULT NOW()
    ,   last_modified       TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ,   PRIMARY KEY (product_image_id)
    ,   FOREIGN KEY (product_id)    REFERENCES products(product_id)     ON DELETE CASCADE
    ,   FOREIGN KEY (image_id)      REFERENCES images(image_id)         ON DELETE CASCADE     
    ,   INDEX  xi_product_image_product_id (product_id)
    ,   INDEX xi_product_image_image_id (image_id)

);

CREATE TABLE orders (
        order_id            INT UNSIGNED            NOT NULL AUTO_INCREMENT
    ,   user_id             INT UNSIGNED            NOT NULL
    ,   order_status        varchar(20)             NOT NULL DEFAULT 'open'
    ,   created_by_user_id  INT                     NOT NULL
    ,   created_date        DATETIME                NOT NULL DEFAULT NOW() 
    ,   last_modified       TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ,   PRIMARY KEY (order_id)
    ,   FOREIGN KEY (user_id)      REFERENCES users(user_id)         ON DELETE CASCADE   
    ,   INDEX xi_order_user_id  (user_id)
);

CREATE TABLE order_lines (
        order_line_id       INT UNSIGNED            NOT NULL AUTO_INCREMENT
    ,   product_id          INT UNSIGNED            NOT NULL
    ,   product_qty         BIGINT(20)              NOT NULL
    ,   product_price       DECIMAL(19,4)           NULL
    ,   order_id            INT UNSIGNED            NOT NULL
    ,   created_date        DATETIME                NOT NULL DEFAULT NOW()
    ,   created_by_user_id  INT                     NOT NULL
    ,   last_modified       TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ,   PRIMARY KEY(order_line_id)
    ,   FOREIGN  KEY (product_id)    REFERENCES products(product_id) ON DELETE CASCADE
    ,   FOREIGN KEY (order_id)      REFERENCES orders(order_id)     ON DELETE CASCADE
    ,   INDEX xi_order_lines_product_id (product_id)
    ,   INDEX xi_order_lines_order_id (order_id)
);


