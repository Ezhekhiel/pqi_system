<div class="card dashboard-card border-0 shadow-sm">

    <div class="card-body">

        <div class="d-flex">

            <div class="icon-box bg-{{ $color }}">

                <i class="{{ $icon }}"></i>

            </div>

            <div class="ms-3">

                <small class="text-uppercase fw-bold text-{{ $color }}">
                    {{ $title }}
                </small>

                <h2 class="fw-bold mb-0">
                    <span id="{{ $id_value }}">{{ $value }}</span>

                    <span class="fs-6 text-muted fw-normal">

                        {{ $unit }}

                    </span>

                </h2>
            </div>

        </div>

    </div>

</div>
