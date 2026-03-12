DELIMITER //

CREATE PROCEDURE drop_all_tables()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE t VARCHAR(255);

    DECLARE cur CURSOR FOR
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_type = 'BASE TABLE';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    SET FOREIGN_KEY_CHECKS = 0;

    OPEN cur;

    loop_tables: LOOP
        FETCH cur INTO t;
        IF done THEN
            LEAVE loop_tables;
        END IF;

        SET @q = CONCAT('DROP TABLE IF EXISTS `', t, '`');
        PREPARE s FROM @q;
        EXECUTE s;
        DEALLOCATE PREPARE s;
    END LOOP;

    CLOSE cur;

    SET FOREIGN_KEY_CHECKS = 1;
END//

CALL drop_all_tables()//

DROP PROCEDURE drop_all_tables//

DELIMITER ;
