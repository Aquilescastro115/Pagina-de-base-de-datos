-- Se asegura que la columna Contrasena exista para el Login.
IF COL_LENGTH('Cliente', 'Contrasena') IS NULL
BEGIN
    ALTER TABLE Cliente ADD Contrasena NVARCHAR(50);
END
GO


IF OBJECT_ID('FK_Venta_Catalogo', 'F') IS NOT NULL
    ALTER TABLE Venta DROP CONSTRAINT FK_Venta_Catalogo;
IF OBJECT_ID('FK_Venta_Cliente', 'F') IS NOT NULL
    ALTER TABLE Venta DROP CONSTRAINT FK_Venta_Cliente;
GO


ALTER TABLE Venta
ADD CONSTRAINT FK_Venta_Catalogo
FOREIGN KEY (num_item) REFERENCES Catalogo(num_item);

ALTER TABLE Venta
ADD CONSTRAINT FK_Venta_Cliente
FOREIGN KEY (id_cliente) REFERENCES Cliente(id_cliente);
GO

-- 1.4. LOG (Tabla para el Trigger)
--------------------------------------------------------------------------------
IF OBJECT_ID('catalogo_Log', 'U') IS NULL
BEGIN
    CREATE TABLE catalogo_Log (
� �     id_Log INT PRIMARY KEY IDENTITY(1,1),
� �     Fecha DATETIME DEFAULT GETDATE(),
� �     Detalle VARCHAR(MAX)
    );
END
GO
------sas
-- 2.1. SP LOGIN
--------------------------------------------------------------------------------
-- 1. Construya un SP de login
IF OBJECT_ID('Sp_Login', 'P') IS NOT NULL DROP PROCEDURE Sp_Login
GO
CREATE PROCEDURE Sp_Login
	@User NVARCHAR (50),
	@Contra Nvarchar (50)
AS
BEGIN
	if exists (select 1 from Cliente where Nombre = @User and Contrasena = @Contra)
	begin
		select 1 as Resultado
	end
else
	begin
		select 0 as Resultado
	end
END
GO

IF OBJECT_ID('sp_InsertarVenta', 'P') IS NOT NULL DROP PROCEDURE sp_InsertarVenta
GO
CREATE PROCEDURE sp_InsertarVenta
� � @fecha Date,
� � @num_item INT,
� � @id_cliente INT�
AS
BEGIN
� � INSERT INTO Venta (fecha, num_item, id_cliente)
� � VALUES (@fecha, @num_item, @id_cliente)
END
GO

-- READ (Incluye nombre de cliente y producto, y costo)
IF OBJECT_ID('Sp_LeerVenta', 'P') IS NOT NULL DROP PROCEDURE Sp_LeerVenta
GO
CREATE PROCEDURE Sp_LeerVenta
AS
BEGIN
	select 
        V.id_venta, 
        C.nombre AS NombreCliente,
        Ct.nombre AS NombreProducto, 
        Ct.costo, 
        V.fecha 
    from Venta V
	inner join catalogo Ct on v.num_item = Ct.num_item
    inner join Cliente C on v.id_cliente = C.id_cliente
    ORDER BY V.id_venta DESC
END
GO

-- UPDATE
IF OBJECT_ID('sp_ActualizarVenta', 'P') IS NOT NULL DROP PROCEDURE sp_ActualizarVenta
GO
CREATE PROCEDURE sp_ActualizarVenta
� � @id_venta INT, -- PK
� � @fecha Date,
� � @num_item INT, -- FK
� � @id_cliente INT -- FK
AS
BEGIN
� � UPDATE Venta
� � SET fecha = @fecha, num_item = @num_item, id_cliente = @id_cliente
� � WHERE id_venta = @id_venta
END
GO

-- DELETE
IF OBJECT_ID('Sp_EliminarVenta', 'P') IS NOT NULL DROP PROCEDURE Sp_EliminarVenta
GO
CREATE PROCEDURE Sp_EliminarVenta
	@id_venta int -- PK
AS
BEGIN
	delete from Venta where id_venta = @id_venta
END
GO

-- 2.3. CRUD CATALOGO (TABLA SECUNDARIA)
--------------------------------------------------------------------------------
-- 3. Construya un CRUD a una de las tablas secundarias (Catalogo)

-- CREATE (Activa el Trigger)
IF OBJECT_ID('SP_insertCatalogo', 'P') IS NOT NULL DROP PROCEDURE SP_insertCatalogo
GO
CREATE PROCEDURE SP_insertCatalogo
	@num_item int, -- PK
	@nombre varchar(100),
	@costo int,
	@stock int
AS
BEGIN
	insert into catalogo (num_item, nombre, costo, stock) values (@num_item, @nombre, @costo, @stock)
END
GO

-- READ
IF OBJECT_ID('sp_LeerCatalogo', 'P') IS NOT NULL DROP PROCEDURE sp_LeerCatalogo
GO
CREATE PROCEDURE sp_LeerCatalogo
AS
BEGIN
� � SELECT num_item, nombre, costo, stock FROM catalogo
END
GO

-- UPDATE
IF OBJECT_ID('sp_ActualizarCatalogo', 'P') IS NOT NULL DROP PROCEDURE sp_ActualizarCatalogo
GO
CREATE PROCEDURE sp_ActualizarCatalogo
� � @num_item INT, -- PK
    @nombre VARCHAR(100), -- Permite actualizar el nombre
� � @costo int,
	@stock int
AS
BEGIN
� � UPDATE catalogo SET nombre = @nombre, costo = @costo, stock = @stock WHERE num_item = @num_item
END
GO

-- DELETE
IF OBJECT_ID('sp_EliminarCatalogo', 'P') IS NOT NULL DROP PROCEDURE sp_EliminarCatalogo
GO
CREATE PROCEDURE sp_EliminarCatalogo
� � @num_item INT -- PK
AS
BEGIN
� � DELETE FROM catalogo WHERE num_item = @num_item
END
GO

-- 2.4. FUNCIONES Y REPORTES
--------------------------------------------------------------------------------
-- 4. Funci�n Escalar
IF OBJECT_ID('FN_CalcularPrecioConIVA', 'FN') IS NOT NULL DROP FUNCTION FN_CalcularPrecioConIVA
GO
CREATE FUNCTION FN_CalcularPrecioConIVA (@costo DECIMAL(10,2))
RETURNS DECIMAL(10,2)
AS
BEGIN
� � RETURN @costo * 1.19
END
GO

-- SP para Reporte de Funci�n Escalar
IF OBJECT_ID('sp_ReportePreciosConIVA', 'P') IS NOT NULL DROP PROCEDURE sp_ReportePreciosConIVA
GO
CREATE PROCEDURE sp_ReportePreciosConIVA
AS
BEGIN
    SELECT 
        nombre, 
        costo, 
        dbo.FN_CalcularPrecioConIVA(CAST(costo AS DECIMAL(10,2))) AS PrecioFinalConIVA
    FROM 
        catalogo;
END
GO

-- 5. Funci�n de Tabla
IF OBJECT_ID('FN_BajoStock', 'TF') IS NOT NULL DROP FUNCTION FN_BajoStock
GO
CREATE FUNCTION FN_BajoStock (@Stock INT)
RETURNS TABLE
AS
RETURN
(
� � SELECT nombre, stock, costo
� � FROM catalogo
� � WHERE stock <= @stock
)
GO

-- SP para Reporte de Funci�n de Tabla
IF OBJECT_ID('sp_ReporteBajoStock', 'P') IS NOT NULL DROP PROCEDURE sp_ReporteBajoStock
GO
CREATE PROCEDURE sp_ReporteBajoStock
    @StockUmbral INT
AS
BEGIN
    SELECT 
        nombre, 
        stock, 
        costo 
    FROM 
        dbo.FN_BajoStock(@StockUmbral);
END
GO

-- 2.5. TRIGGER Y LECTOR DE LOG
--------------------------------------------------------------------------------
-- 6. Operaci�n TRIGGER
IF OBJECT_ID('TG_GuardarCatalogo', 'TR') IS NOT NULL DROP TRIGGER TG_GuardarCatalogo
GO
CREATE TRIGGER TG_GuardarCatalogo
ON Catalogo
AFTER INSERT
AS
BEGIN
    INSERT INTO catalogo_Log (Detalle)
    SELECT ('Se ha creado el producto: ' + inserted.Nombre + ' (ID: ' + CAST(inserted.num_item AS VARCHAR) + ')')
    FROM inserted;
END
GO

-- SP para Leer la tabla Log
IF OBJECT_ID('sp_LeerCatalogoLog', 'P') IS NOT NULL DROP PROCEDURE sp_LeerCatalogoLog
GO
CREATE PROCEDURE sp_LeerCatalogoLog
AS
BEGIN
    SELECT 
        id_Log, 
        Fecha, 
        Detalle 
    FROM 
        catalogo_Log
    ORDER BY 
        id_Log DESC;
END
GO

-- 2.6. ASIGNACI�N DE CONTRASE�AS (NECESARIO PARA EL LOGIN)
--------------------------------------------------------------------------------
-- **EJEMPLO:** Asigna una contrase�a al primer cliente.
UPDATE Cliente SET Contrasena = '12345' WHERE id_cliente = 1; -- Carlos Mu�oz tendr� la contrase�a '12345'

UPDATE Cliente SET nombre = 'Diego' WHERE id_cliente = 1; -- Carlos Mu�oz tendr� la contrase�a '12345'

select* from Cliente