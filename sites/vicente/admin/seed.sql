-- Dados de demonstração da Vicente Corretor de Imóveis.
-- Importe DEPOIS do schema.sql. As fotos aqui são URLs do Unsplash (placeholder) só
-- para o site já nascer populado na apresentação — as fotos reais entram pelo admin.
-- Para zerar a demo antes de ir ao ar: DELETE FROM imoveis; (as fotos caem em cascata).

SET NAMES utf8mb4;

INSERT INTO imoveis
  (id, referencia, titulo, finalidade, tipo, preco, condominio, iptu, descricao,
   dormitorios, suites, banheiros, vagas, area_util, area_total,
   bairro, cidade, uf, mostrar_endereco, caracteristicas, status, destaque) VALUES
(1, 'LI025', 'Casa térrea com quintal amplo no Anália Franco', 'venda', 'casa', 1290000.00, NULL, 320.00,
 'Casa térrea reformada em rua tranquila do Anália Franco. Living integrado à cozinha gourmet, quintal com churrasqueira e espaço para piscina. Pronta para morar.',
 3, 1, 3, 2, 180.00, 250.00, 'Anália Franco', 'São Paulo', 'SP', 0,
 '["churrasqueira","quintal","area_servico","closet"]', 'disponivel', 1),

(2, 'LI031', 'Apartamento alto padrão no Tatuapé', 'venda', 'apartamento', 980000.00, 1150.00, 410.00,
 'Apartamento de 3 dormitórios com varanda gourmet integrada, 2 vagas e lazer completo. Andar alto, face manhã, vista livre. Condomínio com portaria 24h.',
 3, 2, 2, 2, 96.00, NULL, 'Tatuapé', 'São Paulo', 'SP', 0,
 '["varanda_gourmet","piscina","academia","portaria_24h","salao_festas","elevador"]', 'disponivel', 1),

(3, 'LI048', 'Sobrado em condomínio fechado na Vila Formosa', 'venda', 'sobrado', 1650000.00, 680.00, 520.00,
 'Sobrado em condomínio fechado com segurança 24h. Quatro dormitórios, suíte master com closet, quintal e churrasqueira. Acabamento de alto padrão.',
 4, 2, 4, 3, 240.00, 300.00, 'Vila Formosa', 'São Paulo', 'SP', 0,
 '["churrasqueira","portaria_24h","closet","quintal","aquecimento_solar"]', 'disponivel', 1),

(4, 'LI052', 'Apartamento para locação na Mooca', 'aluguel', 'apartamento', 3200.00, 720.00, 95.00,
 'Apartamento de 2 dormitórios mobiliado, pronto para morar, próximo ao metrô. Lazer com piscina e academia. Locação rápida.',
 2, 1, 1, 1, 62.00, NULL, 'Mooca', 'São Paulo', 'SP', 0,
 '["mobiliado","piscina","academia","portaria_24h","elevador","ar_condicionado"]', 'disponivel', 1),

(5, 'LI060', 'Casa comercial para locação na Penha', 'aluguel', 'comercial', 6500.00, NULL, 180.00,
 'Imóvel comercial em avenida de grande fluxo, ideal para clínica, escritório ou loja. Amplo salão, copa, banheiros e vaga.',
 0, 0, 2, 1, 120.00, 160.00, 'Penha', 'São Paulo', 'SP', 1,
 '["area_servico"]', 'disponivel', 0),

(6, 'LI073', 'Terreno plano em Vila Matilde', 'venda', 'terreno', 720000.00, NULL, 140.00,
 'Terreno plano e regular, ótima topografia, em região consolidada e bem servida de comércio e transporte. Documentação em dia.',
 0, 0, 0, 0, NULL, 250.00, 'Vila Matilde', 'São Paulo', 'SP', 0,
 '[]', 'disponivel', 0);

-- Fotos (Unsplash — placeholders). arquivo guarda a URL completa; o site detecta http e usa direto.
INSERT INTO imovel_fotos (imovel_id, arquivo, ordem, capa) VALUES
(1, 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1200&q=70', 0, 1),
(1, 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=70', 1, 0),
(1, 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1200&q=70', 2, 0),

(2, 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=70', 0, 1),
(2, 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=70', 1, 0),
(2, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=70', 2, 0),

(3, 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?auto=format&fit=crop&w=1200&q=70', 0, 1),
(3, 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1200&q=70', 1, 0),
(3, 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=70', 2, 0),

(4, 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1200&q=70', 0, 1),
(4, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=70', 1, 0),

(5, 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=70', 0, 1),
(5, 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1200&q=70', 1, 0),

(6, 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=70', 0, 1);
