/**
 * Metadata Configuration Type Definitions
 * 
 * Defines the structure for metadata-driven UI configuration
 */

export interface ModuleMetadata {
  name: string;
  label: string;
  icon?: string;
  description?: string;
  enabled: boolean;
  order: number;
  permissions: string[];
  routes: RouteMetadata[];
  navigation: NavigationItemMetadata[];
  features: Record<string, boolean>;
}

export interface RouteMetadata {
  path: string;
  name: string;
  component: string;
  meta: {
    title: string;
    permissions: string[];
    breadcrumbs: BreadcrumbMetadata[];
    requiresAuth: boolean;
    layout?: string;
  };
  children?: RouteMetadata[];
}

export interface NavigationItemMetadata {
  id: string;
  label: string;
  icon?: string;
  path?: string;
  order: number;
  permissions: string[];
  children?: NavigationItemMetadata[];
  badge?: {
    value: string | number;
    variant: 'primary' | 'success' | 'warning' | 'danger';
  };
  visible: boolean;
}

export interface BreadcrumbMetadata {
  label: string;
  path?: string;
}

export interface FormMetadata {
  id: string;
  title: string;
  description?: string;
  sections: FormSectionMetadata[];
  validation?: Record<string, any>;
  submitButton?: {
    label: string;
    variant: string;
  };
  cancelButton?: {
    label: string;
  };
}

export interface FormSectionMetadata {
  id: string;
  title?: string;
  description?: string;
  fields: FormFieldMetadata[];
  collapsible?: boolean;
  defaultExpanded?: boolean;
}

export interface FormFieldMetadata {
  name: string;
  label: string;
  type: 'text' | 'number' | 'email' | 'password' | 'textarea' | 'select' | 'multiselect' | 'checkbox' | 'radio' | 'date' | 'datetime' | 'time' | 'file' | 'custom';
  placeholder?: string;
  defaultValue?: any;
  required?: boolean;
  disabled?: boolean;
  readonly?: boolean;
  visible?: boolean;
  validation?: Record<string, any>;
  options?: FieldOptionMetadata[];
  dependsOn?: {
    field: string;
    value: any;
  };
  helpText?: string;
  prefix?: string;
  suffix?: string;
  min?: number;
  max?: number;
  step?: number;
  rows?: number;
  cols?: number;
  multiple?: boolean;
  accept?: string;
  customComponent?: string;
  customProps?: Record<string, any>;
}

export interface FieldOptionMetadata {
  label: string;
  value: any;
  disabled?: boolean;
}

export interface TableMetadata {
  id: string;
  title?: string;
  apiEndpoint: string;
  columns: TableColumnMetadata[];
  actions?: TableActionMetadata[];
  bulkActions?: BulkActionMetadata[];
  filters?: FilterMetadata[];
  searchable?: boolean;
  sortable?: boolean;
  pagination?: {
    enabled: boolean;
    pageSize: number;
    pageSizeOptions: number[];
  };
  exportable?: boolean;
}

export interface TableColumnMetadata {
  key: string;
  label: string;
  type: 'text' | 'number' | 'date' | 'datetime' | 'boolean' | 'badge' | 'custom';
  sortable?: boolean;
  filterable?: boolean;
  visible?: boolean;
  width?: string;
  align?: 'left' | 'center' | 'right';
  formatter?: string;
  customComponent?: string;
  customProps?: Record<string, any>;
}

export interface TableActionMetadata {
  id: string;
  label: string;
  icon?: string;
  variant: 'primary' | 'secondary' | 'success' | 'warning' | 'danger';
  permissions?: string[];
  action: string;
  confirm?: {
    title: string;
    message: string;
  };
}

export interface BulkActionMetadata {
  id: string;
  label: string;
  icon?: string;
  permissions?: string[];
  action: string;
  confirm?: {
    title: string;
    message: string;
  };
}

export interface FilterMetadata {
  name: string;
  label: string;
  type: 'text' | 'select' | 'multiselect' | 'date' | 'daterange' | 'number' | 'boolean';
  options?: FieldOptionMetadata[];
  defaultValue?: any;
}

export interface DashboardMetadata {
  id: string;
  title: string;
  widgets: WidgetMetadata[];
  layout: 'grid' | 'masonry';
}

export interface WidgetMetadata {
  id: string;
  type: 'stat' | 'chart' | 'table' | 'list' | 'custom';
  title: string;
  permissions?: string[];
  position: {
    row: number;
    col: number;
    rowSpan?: number;
    colSpan?: number;
  };
  refreshInterval?: number;
  dataSource: {
    type: 'api' | 'computed' | 'static';
    config: any;
  };
  customComponent?: string;
  customProps?: Record<string, any>;
}

export interface TenantConfiguration {
  id: string;
  name: string;
  domain: string;
  theme: ThemeConfiguration;
  locale: LocaleConfiguration;
  currency: CurrencyConfiguration;
  timezone: string;
  features: Record<string, boolean>;
  modules: Record<string, ModuleMetadata>;
  customization: Record<string, any>;
}

export interface ThemeConfiguration {
  primary: string;
  secondary: string;
  accent: string;
  success: string;
  warning: string;
  danger: string;
  info: string;
  dark: boolean;
  customCss?: string;
}

export interface LocaleConfiguration {
  default: string;
  supported: string[];
  fallback: string;
}

export interface CurrencyConfiguration {
  default: string;
  supported: string[];
  displayFormat: string;
  decimalPlaces: number;
}

export interface PermissionConfiguration {
  user: string[];
  role: string[];
  tenant: string[];
}

export interface WorkflowMetadata {
  id: string;
  name: string;
  entity: string;
  states: WorkflowStateMetadata[];
  transitions: WorkflowTransitionMetadata[];
}

export interface WorkflowStateMetadata {
  id: string;
  label: string;
  type: 'initial' | 'intermediate' | 'final';
  permissions?: string[];
  actions?: string[];
}

export interface WorkflowTransitionMetadata {
  from: string;
  to: string;
  label: string;
  permissions?: string[];
  conditions?: Record<string, any>;
  actions?: string[];
}

/**
 * Entity Metadata for CRUD operations
 */
export interface EntityMetadata {
  id: string;
  name: string;
  singular?: string;
  plural?: string;
  description?: string;
  icon?: string;
  apiEndpoint: string;
  permissions: {
    view: string;
    create: string;
    update: string;
    delete: string;
  };
  fields?: FormFieldMetadata[];
  routes?: {
    list?: string;
    create?: string;
    edit?: string;
    view?: string;
  };
}

/**
 * Extended Module Metadata with entity configuration
 */
export interface ModuleMetadataExtended extends ModuleMetadata {
  config?: {
    entities?: Record<string, EntityMetadata>;
    features?: Record<string, boolean | string | number | any>;
  };
}

/**
 * Action metadata for row/bulk actions
 */
export interface ActionMetadata {
  id: string;
  label: string;
  icon?: string;
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger';
  permissions?: string[];
  action: string;
  confirm?: {
    title: string;
    message: string;
  };
  visible?: boolean | ((row?: any) => boolean);
  disabled?: boolean | ((row?: any) => boolean);
}

/**
 * Enhanced Field Option with groups
 */
export interface FieldOptionMetadataExtended extends FieldOptionMetadata {
  group?: string;
  icon?: string;
  description?: string;
  metadata?: Record<string, any>;
}

/**
 * Field validation rule
 */
export interface FieldValidationRule {
  type: 'required' | 'email' | 'url' | 'min' | 'max' | 'minLength' | 'maxLength' | 'pattern' | 'custom';
  value?: any;
  message?: string;
  validator?: (value: any, formData?: any) => boolean | string;
}

/**
 * Enhanced Form Field with advanced features
 */
export interface FormFieldMetadataExtended extends FormFieldMetadata {
  validationRules?: FieldValidationRule[];
  conditional?: {
    field: string;
    operator: '==' | '!=' | '>' | '<' | '>=' | '<=' | 'in' | 'notIn';
    value: any;
  };
  api?: {
    endpoint: string;
    method?: 'GET' | 'POST';
    params?: Record<string, any>;
    valueField?: string;
    labelField?: string;
  };
  computed?: {
    dependencies: string[];
    compute: (formData: any) => any;
  };
}
