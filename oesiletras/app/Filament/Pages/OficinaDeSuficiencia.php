<?php

namespace App\Filament\Pages;

use App\Filament\Componentes\ImagenOptimizada;
use App\Models\OesiSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * El contenido del sitio de la OESI, entero.
 *
 * Una página y no un recurso porque `oesi_settings` tiene una sola fila: un
 * recurso daría un listado de un elemento y un botón de crear que no debe
 * existir. Las pestañas siguen el orden de la página pública
 * —curso, examen, habilidades, modalidades, cronograma, inscripción,
 * preguntas— para que quien edita encuentre cada cosa donde la ve.
 *
 * **Lo que se toca cada mes es el cronograma**: la imagen y, si cambian, sus
 * formularios. Los enlaces de San Market, que según la propia oficina cambian
 * de código sin avisar, están en la pestaña Tarifario. Cada enlace y cada
 * precio se escriben una sola vez: la página los repite donde haga falta.
 *
 * Las negritas se marcan con dos asteriscos —**así**— en los textos que lo
 * indican. No se admite HTML: lo que se escribe aquí se pinta como texto.
 */
class OficinaDeSuficiencia extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?string $navigationLabel = 'Contenido del sitio';

    protected static ?string $title = 'Oficina de Examen de Suficiencia en Idiomas';

    protected static ?int $navigationSort = 1;

    private const NEGRITAS = 'Para poner algo en negrita, enciérralo entre dos asteriscos: **así**.';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(OesiSetting::query()->firstOrCreate([])->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Publicación')->schema($this->publicacion()),
                        Tab::make('Contacto')->schema($this->contacto()),
                        Tab::make('Sitio')->schema($this->sitio()),
                        Tab::make('Curso')->schema($this->curso()),
                        Tab::make('Examen')->schema($this->examen()),
                        Tab::make('Habilidades')->schema($this->habilidades()),
                        Tab::make('Modalidades')->schema($this->modalidades()),
                        Tab::make('Cronograma')->schema($this->cronograma()),
                        Tab::make('Inscripción')->schema($this->inscripcion()),
                        Tab::make('Tarifario')->schema($this->tarifario()),
                        Tab::make('Preguntas')->schema($this->preguntas()),
                    ]),
            ])
            ->statePath('data');
    }

    /** @return list<mixed> */
    private function publicacion(): array
    {
        return [
            Section::make('Estado de la página')
                ->description('En borrador, la portada del sitio publicado dice «próximamente»: el contenido solo se ve en la vista previa.')
                ->schema([
                    Radio::make('estado')
                        ->hiddenLabel()
                        ->options([
                            OesiSetting::ESTADO_BORRADOR => 'Borrador — en revisión',
                            OesiSetting::ESTADO_PUBLICADO => 'Publicada',
                        ])
                        ->required(),
                ]),

            Section::make('Cifras de la cabecera')
                ->description('Solo salen en el sitio publicado mientras estén marcadas como verificadas: al cambiar una cifra, comprueba que el dato sigue respaldado. Desmarcarlas las retira sin despublicar la página.')
                ->schema([
                    Toggle::make('estadisticas_verificadas')
                        ->label('Las cifras están verificadas y actualizadas'),

                    Repeater::make('estadisticas')
                        ->label('Cifras')
                        ->addActionLabel('Añadir cifra')
                        ->defaultItems(0)
                        ->columns(4)
                        ->itemLabel(fn (array $state): ?string => $state['etiqueta'] ?? null)
                        ->schema([
                            TextInput::make('prefijo')->maxLength(5),
                            TextInput::make('valor')->integer()->minValue(0)->required(),
                            TextInput::make('sufijo')->maxLength(5)->helperText('«+», «%»…'),
                            TextInput::make('etiqueta')->required()->maxLength(60),
                        ]),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function contacto(): array
    {
        return [
            Section::make('Identidad')
                ->columns(2)
                ->schema([
                    TextInput::make('nombre')->label('Nombre de la oficina')->required()->maxLength(255),
                    TextInput::make('institucion')->label('Facultad y universidad')->maxLength(255),
                    Textarea::make('bajada')->label('Texto bajo el nombre')->rows(2)->columnSpanFull(),
                ]),

            Section::make('Contacto')
                ->description('El enlace de WhatsApp se compone con el número y el mensaje: no hace falta escribirlo.')
                ->columns(2)
                ->schema([
                    TextInput::make('whatsapp_numero')
                        ->label('WhatsApp')
                        ->helperText('Con código de país, p. ej. 51914033108.')
                        ->regex('/^\+?[\d\s-]{9,20}$/')
                        ->required(),
                    TextInput::make('whatsapp_mensaje')->label('Mensaje con el que se abre')->maxLength(255),
                    TextInput::make('email')->label('Correo')->email()->required()->maxLength(255),
                    TextInput::make('horario')->label('Horario de atención')->maxLength(255),
                ]),

            Section::make('Redes')
                ->schema([
                    Repeater::make('redes')
                        ->hiddenLabel()
                        ->addActionLabel('Añadir red')
                        ->defaultItems(0)
                        ->columns(2)
                        ->schema([
                            Select::make('red')
                                ->options([
                                    'facebook' => 'Facebook',
                                    'instagram' => 'Instagram',
                                    'whatsapp' => 'Canal de WhatsApp',
                                    'tiktok' => 'TikTok',
                                    'youtube' => 'YouTube',
                                    'linkedin' => 'LinkedIn',
                                ])
                                ->native(false)
                                ->required(),
                            TextInput::make('url')->label('Enlace')->url()->required(),
                        ]),
                ]),
        ];
    }

    /**
     * Lo que identifica al sitio fuera de la página: cabecera, pie, pestaña del
     * navegador y vista previa al compartir un enlace.
     *
     * @return list<mixed>
     */
    private function sitio(): array
    {
        return [
            Section::make('Identidad del sitio')
                ->columns(2)
                ->schema([
                    Textarea::make('descripcion')
                        ->label('Descripción')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Sale en el pie y en lo que se ve al compartir un enlace.'),
                    ImagenOptimizada::make('logo_path', 'sitio', 600, 'Logotipo'),
                    ImagenOptimizada::make('favicon_path', 'sitio', 512, 'Favicon'),
                    Textarea::make('direccion')->label('Dirección')->rows(2),
                    TextInput::make('web_facultad')->label('Web de la Facultad')->url()->maxLength(500),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function curso(): array
    {
        return [
            Section::make('Curso de preparación')
                ->description('Un recurso relacionado, que dicta el CEID: no es la inscripción al examen.')
                ->columns(2)
                ->schema([
                    TextInput::make('curso_titulo')->label('Título')->maxLength(255),
                    TextInput::make('curso_url')->label('Página de información')->url()->maxLength(500),
                    Textarea::make('curso_bajada')->label('Presentación')->rows(2)->columnSpanFull(),
                    TextInput::make('curso_imagen')->label('Flyer (URL de la imagen)')->url()->maxLength(500),
                    TextInput::make('curso_imagen_alt')
                        ->label('Descripción del flyer')
                        ->helperText('La lee quien no ve la imagen.')
                        ->requiredWith('curso_imagen')
                        ->maxLength(255),
                    TextInput::make('curso_whatsapp_numero')
                        ->label('WhatsApp del curso')
                        ->helperText('El del curso, que no es el de la oficina.')
                        ->regex('/^\+?[\d\s-]{9,20}$/'),
                    TextInput::make('curso_whatsapp_mensaje')->label('Mensaje con el que se abre')->maxLength(255),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function examen(): array
    {
        return [
            Section::make('El examen')
                ->schema([
                    Textarea::make('examen_objetivo')->label('Objetivo y lenguas')->rows(3),
                    Repeater::make('examen_destinatarios')
                        ->label('A quién va dirigido')
                        ->simple(TextInput::make('texto')->required())
                        ->addActionLabel('Añadir destinatario')
                        ->defaultItems(0),
                    Textarea::make('examen_jurado')->label('Sobre el jurado')->rows(2),
                ]),

            Section::make('Escala de calificación')
                ->description('Se publica tal como se escribe aquí: rangos y notas son de la oficina.')
                ->schema([
                    TextInput::make('escala_intro')->label('Introducción')->maxLength(255),
                    Repeater::make('escala')
                        ->label('Niveles')
                        ->addActionLabel('Añadir nivel')
                        ->defaultItems(0)
                        ->columns(3)
                        ->itemLabel(fn (array $state): ?string => trim(($state['letra'] ?? '') . ' ' . ($state['nivel'] ?? '')) ?: null)
                        ->schema([
                            TextInput::make('letra')->required()->maxLength(3),
                            TextInput::make('nivel')->required()->maxLength(50),
                            TextInput::make('rango')->required()->maxLength(20),
                            Repeater::make('subniveles')
                                ->columnSpanFull()
                                ->columns(3)
                                ->addActionLabel('Añadir subnivel')
                                ->schema([
                                    TextInput::make('codigo')->label('Subnivel')->required()->maxLength(5),
                                    TextInput::make('nota')->required()->maxLength(20),
                                    TextInput::make('descripcion')->label('Descripción')->maxLength(60),
                                ]),
                        ]),
                ]),

            Section::make('Vigencia del certificado')
                ->schema([
                    TextInput::make('vigencia_intro')->label('Introducción')->maxLength(255),
                    Repeater::make('vigencia')
                        ->hiddenLabel()
                        ->addActionLabel('Añadir fila')
                        ->defaultItems(0)
                        ->columns(2)
                        ->schema([
                            TextInput::make('nivel')->required()->maxLength(60),
                            TextInput::make('vigencia')->required()->maxLength(60),
                        ]),
                    Textarea::make('vigencia_nota')->label('Nota')->rows(2),
                ]),

            Section::make('Resultados')
                ->description(self::NEGRITAS)
                ->schema([
                    Textarea::make('resultados_aprobados')->label('Aprobados')->rows(3),
                    Textarea::make('resultados_desaprobados')->label('Desaprobados')->rows(2),
                    Textarea::make('resultados_virtual')->label('Modalidad virtual')->rows(2),
                ]),

            Section::make('Requisitos')
                ->description(self::NEGRITAS)
                ->schema([
                    Textarea::make('requisitos')->hiddenLabel()->rows(3),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function habilidades(): array
    {
        return [
            Section::make()->schema([
                Textarea::make('habilidades_intro')->label('Introducción')->rows(2),
                Repeater::make('habilidades')
                    ->label('Habilidades evaluadas')
                    ->addActionLabel('Añadir habilidad')
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['titulo'] ?? null)
                    ->columns(2)
                    ->schema([
                        TextInput::make('titulo')->required()->maxLength(100),
                        Select::make('icono')
                            ->options([
                                'lectura' => 'Libro (lectura)',
                                'escritura' => 'Pluma (escritura)',
                                'audicion' => 'Auriculares (audición)',
                                'oral' => 'Diálogo (expresión oral)',
                            ])
                            ->native(false),
                        Textarea::make('descripcion')->required()->rows(3)->columnSpanFull(),
                    ]),
            ]),
        ];
    }

    /** @return list<mixed> */
    private function modalidades(): array
    {
        return [
            Section::make()
                ->description('Cada bloque es una tarjeta de la página. ' . self::NEGRITAS)
                ->schema([
                    Textarea::make('modalidades_intro')->label('Introducción')->rows(2),
                    Repeater::make('modalidades')
                        ->label('Bloques de indicaciones')
                        ->addActionLabel('Añadir bloque')
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['titulo'] ?? null)
                        ->columns(2)
                        ->schema([
                            TextInput::make('titulo')->required()->maxLength(100),
                            Select::make('modalidad')
                                ->options(['presencial' => 'Presencial', 'virtual' => 'Virtual'])
                                ->native(false)
                                ->required(),
                            Select::make('icono')
                                ->options([
                                    'presencial' => 'Edificio',
                                    'virtual' => 'Portátil',
                                    'antes' => 'Reloj de arena',
                                    'durante' => 'Prohibido',
                                ])
                                ->native(false),
                            TextInput::make('subtitulo')->maxLength(100),
                            Textarea::make('introduccion')->label('Introducción')->rows(2)->columnSpanFull(),
                            Repeater::make('reglas')
                                ->label('Indicaciones')
                                ->simple(Textarea::make('texto')->rows(2)->required())
                                ->addActionLabel('Añadir indicación')
                                ->columnSpanFull(),
                            Textarea::make('nota')->rows(2)->columnSpanFull(),
                            Select::make('nota_tipo')
                                ->label('La nota es')
                                ->options(['informacion' => 'Informativa', 'aviso' => 'Una advertencia'])
                                ->default('informacion')
                                ->native(false),
                        ]),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function cronograma(): array
    {
        return [
            Section::make('Cronograma del mes')
                ->description('Cambia cada mes. La página no guarda fechas: muestra la imagen vigente y pide confirmarlas. Los formularios de la convocatoria salen en la página de Inscripción, en el paso 2.')
                ->columns(2)
                ->schema([
                    Textarea::make('cronograma_intro')->label('Introducción')->rows(2)->columnSpanFull(),
                    TextInput::make('cronograma_imagen')->label('Imagen del cronograma (URL)')->url()->maxLength(500),
                    TextInput::make('cronograma_imagen_alt')
                        ->label('Descripción de la imagen')
                        ->helperText('Mejor si dice el periodo: «Cronograma de exámenes, 21 al 28 de septiembre».')
                        ->requiredWith('cronograma_imagen')
                        ->maxLength(255),
                    Textarea::make('cronograma_aviso')->label('Aviso sobre las fechas')->rows(2)->columnSpanFull(),
                    Repeater::make('cronograma_formularios')
                        ->label('Formularios de esta convocatoria')
                        ->addActionLabel('Añadir formulario')
                        ->defaultItems(0)
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('nombre')->required()->maxLength(60),
                            TextInput::make('url')->label('Enlace')->url()->required(),
                            TextInput::make('descripcion')
                                ->label('Para quién es')
                                ->helperText('Una línea bajo el nombre, p. ej. «Estudiantes y egresados de pregrado».')
                                ->maxLength(120)
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function inscripcion(): array
    {
        return [
            Section::make('Proceso')
                ->columns(2)
                ->schema([
                    Textarea::make('inscripcion_intro')->label('Introducción')->rows(2)->columnSpanFull(),
                    TextInput::make('proceso_imagen_1')->label('Pasos del proceso (URL)')->url()->maxLength(500),
                    TextInput::make('proceso_imagen_1_alt')->label('Descripción')->requiredWith('proceso_imagen_1')->maxLength(255),
                    TextInput::make('proceso_imagen_2')->label('Pasos finales (URL)')->url()->maxLength(500),
                    TextInput::make('proceso_imagen_2_alt')->label('Descripción')->requiredWith('proceso_imagen_2')->maxLength(255),
                    TextInput::make('medio_pago_imagen')->label('Medio de pago (URL)')->url()->maxLength(500),
                    TextInput::make('medio_pago_imagen_alt')->label('Descripción')->requiredWith('medio_pago_imagen')->maxLength(255),
                ]),

            Section::make('Costos de inscripción')
                ->description('Los importes, en soles y sin símbolo: la página les da formato.')
                ->schema([
                    Textarea::make('costos_intro')->label('Introducción')->rows(2),
                    Repeater::make('costos')
                        ->hiddenLabel()
                        ->addActionLabel('Añadir costo')
                        ->defaultItems(0)
                        ->columns(4)
                        ->schema([
                            Textarea::make('concepto')->required()->rows(2)->columnSpan(3),
                            TextInput::make('monto')->label('Importe (S/)')->numeric()->minValue(0)->required(),
                        ]),
                ]),

            Section::make('Dónde pagar')
                ->description(self::NEGRITAS)
                ->columns(2)
                ->schema([
                    TextInput::make('pago_banco')->label('Banco')->maxLength(255),
                    TextInput::make('pago_codigo')->label('Código de pago')->maxLength(255),
                    Repeater::make('pago_advertencias')
                        ->label('Advertencias')
                        ->simple(Textarea::make('texto')->rows(2)->required())
                        ->addActionLabel('Añadir advertencia')
                        ->columnSpanFull(),
                ]),

            Section::make('Documentos y formularios')
                ->schema([
                    Repeater::make('documentos')
                        ->label('Documentos requeridos')
                        ->helperText('Se numeran a., b., c. en la página.')
                        ->simple(Textarea::make('texto')->rows(2)->required())
                        ->addActionLabel('Añadir documento'),
                    Repeater::make('formularios')
                        ->label('Formularios de inscripción')
                        ->helperText('Para otros trámites. En la página van aparte de los de la convocatoria (pestaña Cronograma), con su descripción.')
                        ->addActionLabel('Añadir formulario')
                        ->defaultItems(0)
                        ->columns(2)
                        ->schema([
                            TextInput::make('nombre')->required()->maxLength(100),
                            TextInput::make('url')->label('Enlace')->url()->required(),
                            TextInput::make('descripcion')
                                ->label('Cuándo se usa')
                                ->helperText('Una línea bajo el nombre: quien llega tiene que saber si es el suyo o el de la convocatoria.')
                                ->maxLength(120)
                                ->columnSpanFull(),
                        ]),
                    Textarea::make('inscripcion_cierre')->label('Texto final')->rows(2),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function tarifario(): array
    {
        return [
            Section::make('Tarifario y enlaces de pago')
                ->description('Si San Market cambia el código de un concepto, se corrige aquí y en ningún otro sitio. Los botones de la página solo llevan a San Market: aquí no se cobra nada.')
                ->schema([
                    Repeater::make('tarifario')
                        ->hiddenLabel()
                        ->addActionLabel('Añadir concepto')
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['concepto'] ?? null)
                        ->columns(2)
                        ->schema([
                            TextInput::make('concepto')->required()->maxLength(255)->columnSpanFull(),
                            TextInput::make('monto')->label('Importe (S/)')->numeric()->minValue(0)->required(),
                            TextInput::make('distintivo')
                                ->helperText('Una etiqueta junto al concepto, p. ej. «Más popular». Vacío, ninguna.')
                                ->maxLength(30),
                            TextInput::make('url_pago')
                                ->label('Enlace de San Market')
                                ->url()
                                ->columnSpanFull(),
                        ]),
                    Textarea::make('tarifario_nota')->label('Nota')->rows(2),
                ]),
        ];
    }

    /** @return list<mixed> */
    private function preguntas(): array
    {
        return [
            Section::make()->schema([
                Textarea::make('faqs_intro')->label('Introducción')->rows(2),
                Repeater::make('faqs')
                    ->label('Preguntas frecuentes')
                    ->addActionLabel('Añadir pregunta')
                    ->defaultItems(0)
                    ->collapsible()
                    ->collapsed()
                    ->reorderable()
                    ->itemLabel(fn (array $state): ?string => $state['pregunta'] ?? null)
                    ->schema([
                        TextInput::make('pregunta')->required()->maxLength(255),
                        Select::make('categoria')
                            ->label('Tema')
                            ->helperText('La página agrupa las preguntas por tema. Sin tema, va a «Otras preguntas».')
                            ->options(OesiSetting::TEMAS_FAQ)
                            ->native(false),
                        Textarea::make('respuesta')->required()->rows(3),
                        Repeater::make('lista')
                            ->label('Viñetas tras la respuesta (opcional)')
                            ->simple(TextInput::make('texto')->required())
                            ->addActionLabel('Añadir viñeta')
                            ->defaultItems(0),
                    ]),
            ]),
        ];
    }

    /**
     * Filament 5 no pinta el formulario solo: la página declara su contenido, y
     * `EmbeddedSchema` enchufa el formulario dentro de un <form> con su botón,
     * el atajo de teclado y la barra de acciones fija.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->contenidoDelFormulario()]);
    }

    private function contenidoDelFormulario(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('guardar')
            ->footer([
                Actions::make($this->getFormActions())->key('form-actions'),
            ]);
    }

    /**
     * La vista previa abre el sitio tal como está guardado, borrador incluido.
     * Hay que guardar antes: previsualiza lo que hay en la base, no lo que hay
     * escrito en el formulario.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('vistaPrevia')
                ->label('Vista previa')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn (): string => route('gestion.vista-previa.sitio'))
                ->openUrlInNewTab(),
        ];
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar cambios')
                ->submit('guardar')
                ->keyBindings(['mod+s']),
        ];
    }

    public function guardar(): void
    {
        $oesi = OesiSetting::query()->firstOrCreate([]);
        $oesi->update($this->form->getState());

        Notification::make()
            ->title('OESI guardada')
            ->body($oesi->estaPublicada()
                ? 'El sitio se reconstruye solo; el cambio tarda unos minutos en verse.'
                : 'Sigue en borrador: los cambios se ven en la vista previa, no en el sitio publicado.')
            ->success()
            ->send();
    }
}
