import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import classnames from 'classnames';
import { setScreenOptions, getScreenOptions } from '../store/screenOptions';
import useI18n from '../hooks/useI18n';

const mapStateToProps = (state) => ({
  settings: getScreenOptions(state).settings,
});

const mapDispatchToProps = {
  setScreenOptions,
};

const ScreenOptions = (props) => {
  const {
    className,
    settings,
    setScreenOptions,
  } = props;

  const { __ } = useI18n();

  const handleChange = (field) => (event) => {
    event.persist();
    setScreenOptions({ [field]: event.target.checked });
  };

  const content = (
    <fieldset className={classnames('metabox-prefs', className)}>
      <legend>{__('Show columns', 'stock-manager')}</legend>

      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.thumbnail} onChange={handleChange('thumbnail')} />
        {' '}
        {__('Thumbnail', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.productName} onChange={handleChange('productName')} />
        {' '}
        {__('Product name', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.price} onChange={handleChange('price')} />
        {' '}
        {__('Price', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.salePrice} onChange={handleChange('salePrice')} />
        {' '}
        {__('Sale price', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.weight} onChange={handleChange('weight')} />
        {' '}
        {__('Weight', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.manageStock} onChange={handleChange('manageStock')} />
        {' '}
        {__('Manage stock', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.stockStatus} onChange={handleChange('stockStatus')} />
        {' '}
        {__('Stock status', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.backorders} onChange={handleChange('backorders')} />
        {' '}
        {__('Backorders', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.stock} onChange={handleChange('stock')} />
        {' '}
        {__('Stock', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.taxStatus} onChange={handleChange('taxStatus')} />
        {' '}
        {__('Tax status', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.taxClass} onChange={handleChange('taxClass')} />
        {' '}
        {__('Tax class', 'stock-manager')}
      </label>
      <label>
        <input type="checkbox" className="hide-column-tog" checked={settings.shippingClass} onChange={handleChange('shippingClass')} />
        {' '}
        {__('Shipping class', 'stock-manager')}
      </label>
    </fieldset>
  );

  // Show settings in Screen Options
  if (document.getElementById('adv-settings')) {
    return ReactDOM.createPortal(content, document.getElementById('adv-settings'));
  }

  return content;
};

ScreenOptions.propTypes = {
  className: PropTypes.string,
  setScreenOptions: PropTypes.func,
  settings: PropTypes.object,
};

ScreenOptions.defaultProps = {
  className: undefined,
};

export default connect(mapStateToProps, mapDispatchToProps)(ScreenOptions);
