<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>
        Filtros
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="mb-3">
                <label class="form-label" for="pesquisa">
                    <i class="bi bi-search me-1"></i>
                    Pesquisar
                </label>
                <input type="text" class="form-control" id="pesquisa" name="pesquisa" placeholder="Digite para buscar...">
            </div>
            
            <div class="mb-3">
                <label class="form-label" for="status">
                    <i class="bi bi-check-circle me-1"></i>
                    Status
                </label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="execucao">Em Execução</option>
                    <option value="concluido">Concluído</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-2"></i>
                Filtrar
            </button>
        </form>
    </div>
</div>

<!-- Legenda -->
<div class="card mb-3">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-secondary">
                <i class="bi bi-circle-fill me-1"></i>
                Pendente
            </span>
            <span class="badge bg-warning">
                <i class="bi bi-circle-fill me-1"></i>
                Em Execução
            </span>
            <span class="badge bg-success">
                <i class="bi bi-circle-fill me-1"></i>
                Concluído
            </span>
            <span class="badge bg-danger">
                <i class="bi bi-circle-fill me-1"></i>
                Inativo
            </span>
        </div>
    </div>
</div>

<!-- Listagem -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>
            Planilhas
        </span>
        <span class="badge bg-white text-dark">5 itens</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 40%;">Comum</th>
                    <th style="width: 25%;">Data</th>
                    <th style="width: 20%;">Status</th>
                    <th style="width: 15%;" class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="fw-bold">Planilha 001</div>
                        <small class="text-muted">SP/São Paulo</small>
                    </td>
                    <td>
                        <small>24/10/2025</small>
                    </td>
                    <td>
                        <span class="badge bg-success">Concluído</span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-outline-primary" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fw-bold">Planilha 002</div>
                        <small class="text-muted">RJ/Rio de Janeiro</small>
                    </td>
                    <td>
                        <small>23/10/2025</small>
                    </td>
                    <td>
                        <span class="badge bg-warning">Em Execução</span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-outline-primary" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fw-bold">Planilha 003</div>
                        <small class="text-muted">MG/Belo Horizonte</small>
                    </td>
                    <td>
                        <small>22/10/2025</small>
                    </td>
                    <td>
                        <span class="badge bg-secondary">Pendente</span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-outline-primary" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <tr class="table-secondary">
                    <td>
                        <div class="fw-bold text-muted">Planilha 004</div>
                        <small class="text-muted">PR/Curitiba</small>
                    </td>
                    <td>
                        <small class="text-muted">21/10/2025</small>
                    </td>
                    <td>
                        <span class="badge bg-danger">Inativo</span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-outline-primary" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fw-bold">Planilha 005</div>
                        <small class="text-muted">RS/Porto Alegre</small>
                    </td>
                    <td>
                        <small>20/10/2025</small>
                    </td>
                    <td>
                        <span class="badge bg-success">Concluído</span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-outline-primary" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<nav aria-label="Navegação de página" class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <li class="page-item disabled">
            <a class="page-link" href="#" tabindex="-1">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item">
            <a class="page-link" href="#">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>

<!-- Estatísticas -->
<div class="row g-2 mt-3">
    <div class="col-6">
        <div class="card text-center">
            <div class="card-body p-2">
                <div class="fs-4 fw-bold text-gradient">12</div>
                <small class="text-muted">Pendentes</small>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card text-center">
            <div class="card-body p-2">
                <div class="fs-4 fw-bold text-success">45</div>
                <small class="text-muted">Concluídos</small>
            </div>
        </div>
    </div>
</div>
